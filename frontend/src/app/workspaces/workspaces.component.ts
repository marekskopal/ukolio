import {ChangeDetectionStrategy, Component, computed, inject, OnInit, signal} from '@angular/core';
import {FormsModule} from '@angular/forms';
import {User} from '@app/models/user';
import {Invitation, Workspace, WorkspaceMember, WorkspaceRole} from '@app/models/workspace';
import {AlertService} from '@app/services/alert.service';
import {CurrentUserService} from '@app/services/current-user.service';
import {PermissionsService} from '@app/services/permissions.service';
import {WorkspaceService} from '@app/services/workspace.service';
import {TranslatePipe, TranslateService} from '@ngx-translate/core';

@Component({
    selector: 'uk-workspaces',
    standalone: true,
    imports: [FormsModule, TranslatePipe],
    changeDetection: ChangeDetectionStrategy.OnPush,
    templateUrl: './workspaces.component.html',
    styleUrl: './workspaces.component.scss',
})
export class WorkspacesComponent implements OnInit {
    private readonly workspaceService = inject(WorkspaceService);
    private readonly currentUserService = inject(CurrentUserService);
    private readonly permissionsService = inject(PermissionsService);
    private readonly alertService = inject(AlertService);
    private readonly translate = inject(TranslateService);

    protected readonly loading = signal(true);
    protected readonly workspaces = this.workspaceService.workspaces;
    protected readonly user = signal<User | null>(null);
    protected readonly selected = signal<Workspace | null>(null);
    protected readonly members = signal<WorkspaceMember[]>([]);
    protected readonly invitations = signal<Invitation[]>([]);
    protected readonly inviteEmail = signal('');
    protected readonly inviteRole = signal<WorkspaceRole>('Member');

    protected readonly isSystemAdmin = this.permissionsService.isSystemAdmin;
    protected readonly canManageWorkspace = computed<boolean>(() => this.permissionsService.canManageWorkspace(this.members()));
    protected readonly canManageMembers = computed<boolean>(() => this.permissionsService.canManageMembers(this.members()));
    protected readonly canTransferOwnership = computed<boolean>(() => this.permissionsService.canTransferOwnership(this.members()));
    protected readonly invitableRoles = computed<WorkspaceRole[]>(() => this.permissionsService.invitableRoles(this.members()));

    public async ngOnInit(): Promise<void> {
        this.loading.set(true);
        try {
            const [user] = await Promise.all([this.currentUserService.load(), this.workspaceService.loadAll()]);
            this.user.set(user);
            const current = this.workspaces().find((w) => w.id === user.currentWorkspaceId) ?? this.workspaces()[0] ?? null;
            if (current !== null) {
                await this.select(current);
            }
        } finally {
            this.loading.set(false);
        }
    }

    protected canChangeRoleOf(member: WorkspaceMember): boolean {
        return this.permissionsService.canChangeRoleOf(this.members(), member);
    }

    protected canRemoveMember(member: WorkspaceMember): boolean {
        return this.permissionsService.canRemoveMember(this.members(), member);
    }

    protected async select(ws: Workspace): Promise<void> {
        this.selected.set(ws);
        const [members, invitations] = await Promise.all([
            this.workspaceService.getMembers(ws.id),
            this.workspaceService.getInvitations(ws.id).catch(() => []),
        ]);
        this.members.set(members);
        this.invitations.set(invitations);
        const allowed = this.permissionsService.invitableRoles(members);
        if (!allowed.includes(this.inviteRole())) {
            this.inviteRole.set(allowed[0] ?? 'Member');
        }
    }

    protected async rename(): Promise<void> {
        const ws = this.selected();
        if (ws === null || !this.canManageWorkspace()) {
            return;
        }
        const promptText = await this.translate.instant('app.workspaces.renamePrompt') as string;
        const name = prompt(promptText, ws.name);
        if (name === null || name.trim() === '' || name.trim() === ws.name) {
            return;
        }
        try {
            const updated = await this.workspaceService.update(ws.id, name.trim());
            this.selected.set(updated);
            this.alertService.success(await this.translate.instant('app.workspaces.renamed') as string);
        } catch {
            // error interceptor
        }
    }

    protected async invite(): Promise<void> {
        const ws = this.selected();
        const email = this.inviteEmail().trim();
        if (ws === null || email === '' || !this.canManageMembers()) {
            return;
        }
        const role = this.inviteRole();
        try {
            const invitation = await this.workspaceService.createInvitation(ws.id, email, role);
            this.invitations.update((all) => [invitation, ...all]);
            this.inviteEmail.set('');
            this.alertService.success(await this.translate.instant('app.workspaces.invitationSent', {email}) as string);
        } catch {
            // error interceptor
        }
    }

    protected async cancelInvitation(invitation: Invitation): Promise<void> {
        const confirmMessage = await this.translate.instant('app.workspaces.cancelInvitationConfirm', {email: invitation.email}) as string;
        if (!confirm(confirmMessage)) {
            return;
        }
        try {
            await this.workspaceService.deleteInvitation(invitation.id);
            this.invitations.update((all) => all.filter((i) => i.id !== invitation.id));
        } catch {
            // error interceptor
        }
    }

    protected async changeMemberRole(member: WorkspaceMember, role: WorkspaceRole): Promise<void> {
        const ws = this.selected();
        if (ws === null || role === member.role) {
            return;
        }
        try {
            const updated = await this.workspaceService.changeMemberRole(ws.id, member.userId, role);
            this.members.update((all) => all.map((m) => (m.userId === member.userId ? updated : m)));
            this.alertService.success(await this.translate.instant('app.workspaces.roleChanged') as string);
        } catch {
            // error interceptor
        }
    }

    protected async transferOwnership(member: WorkspaceMember): Promise<void> {
        const ws = this.selected();
        if (ws === null || !this.canTransferOwnership()) {
            return;
        }
        const confirmMessage = await this.translate.instant('app.workspaces.transferOwnershipConfirm', {
            name: member.name,
            workspace: ws.name,
        }) as string;
        if (!confirm(confirmMessage)) {
            return;
        }
        try {
            const updated = await this.workspaceService.transferOwnership(ws.id, member.userId);
            this.selected.set(updated);
            await this.workspaceService.loadAll();
            const refreshed = await this.workspaceService.getMembers(ws.id);
            this.members.set(refreshed);
            this.alertService.success(await this.translate.instant('app.workspaces.ownershipTransferred') as string);
        } catch {
            // error interceptor
        }
    }

    protected async removeMember(member: WorkspaceMember): Promise<void> {
        const ws = this.selected();
        if (ws === null) {
            return;
        }
        const confirmMessage = await this.translate.instant('app.workspaces.removeMemberConfirm', {
            name: member.name,
            workspace: ws.name,
        }) as string;
        if (!confirm(confirmMessage)) {
            return;
        }
        try {
            await this.workspaceService.removeMember(ws.id, member.userId);
            this.members.update((all) => all.filter((m) => m.userId !== member.userId));
            this.alertService.success(await this.translate.instant('app.workspaces.memberRemoved') as string);
        } catch {
            // error interceptor
        }
    }

    protected async deleteWorkspace(): Promise<void> {
        const ws = this.selected();
        if (ws === null || !this.canManageWorkspace()) {
            return;
        }
        const confirmMessage = await this.translate.instant('app.workspaces.deleteConfirm', {name: ws.name}) as string;
        if (!confirm(confirmMessage)) {
            return;
        }
        try {
            await this.workspaceService.delete(ws.id);
            this.alertService.success(await this.translate.instant('app.workspaces.deleted') as string);
            this.selected.set(null);
            const next = this.workspaces()[0];
            if (next !== undefined) {
                await this.select(next);
            }
        } catch {
            // error interceptor
        }
    }

    protected updateInviteEmail(value: string): void {
        this.inviteEmail.set(value);
    }

    protected updateInviteRole(value: string): void {
        if (value === 'Admin' || value === 'Member') {
            this.inviteRole.set(value);
        }
    }
}
