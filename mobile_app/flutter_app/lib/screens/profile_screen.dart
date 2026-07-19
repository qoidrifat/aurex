import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../core/theme/spacing.dart';
import '../providers/auth_provider.dart';
import '../router.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;

    return Scaffold(
      appBar: AppBar(
        title: Text('Profile', style: AurexTypography.heading2),
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: AppSpacing.paddingScreen,
          child: Column(
            children: [
              // ── Avatar ────────────────────────────
              Semantics(
                header: true,
                label: 'Profil ${user?.name ?? 'User'}',
                child: Column(
                  children: [
                    CircleAvatar(
                      radius: AppSpacing.avatarRadius,
                      backgroundColor: AurexColors.olive,
                      child: Text(
                        _getInitials(user?.name ?? 'User'),
                        style: AurexTypography.heading1.copyWith(
                          color: AurexColors.cream,
                          fontSize: AppSpacing.avatarTextSize,
                        ),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    Text(
                      user?.name ?? 'User',
                      style: AurexTypography.heading1,
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(
                          Icons.email_outlined,
                          size: AppSpacing.iconSm,
                          color: AurexColors.grey,
                        ),
                        const SizedBox(width: AppSpacing.xs),
                        Text(
                          user?.email ?? 'No email',
                          style: AurexTypography.bodyMedium,
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: AppSpacing.xxl),

              // ── Menu Items ────────────────────────
              Expanded(
                child: Column(
                  children: [
                    _MenuTile(
                      icon: Icons.auto_awesome,
                      title: 'New Analysis',
                      subtitle: 'Upload and analyze your style',
                      onTap: () => context.goNamed('upload'),
                    ),
                    _MenuTile(
                      icon: Icons.history,
                      title: 'Analysis History',
                      subtitle: 'View your past analyses',
                      onTap: () => context.pushNamed('history'),
                    ),
                    _MenuTile(
                      icon: Icons.settings,
                      title: 'Settings',
                      subtitle: 'App preferences and account',
                      onTap: () => context.pushNamed('settings'),
                    ),
                    _MenuTile(
                      icon: Icons.help_outline,
                      title: 'Help & Support',
                      subtitle: 'FAQs and contact us',
                      onTap: () => context.pushNamed('help'),
                    ),
                  ],
                ),
              ),

              // ── Logout Button ─────────────────────
              SafeArea(
                child: SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () => _confirmLogout(context),
                    icon: const Icon(Icons.logout, color: AurexColors.rust),
                    label: Text(
                      'Logout',
                      style: AurexTypography.buttonText.copyWith(
                        color: AurexColors.rust,
                      ),
                    ),
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: AurexColors.rust),
                      padding: AppSpacing.paddingButton,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _confirmLogout(BuildContext context) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => _LogoutDialog(parentContext: context),
    );
  }

  String _getInitials(String name) {
    final names = name.split(' ');
    if (names.length >= 2) {
      return '${names.first[0]}${names.last[0]}'.toUpperCase();
    }
    return name.isNotEmpty ? name[0].toUpperCase() : 'U';
  }
}

/// Menu tile dengan micro-interaction untuk halaman profil.
class _MenuTile extends StatefulWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _MenuTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  State<_MenuTile> createState() => _MenuTileState();
}

class _MenuTileState extends State<_MenuTile> {
  bool _isPressed = false;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: AurexColors.charcoal.withValues(alpha: 0.3),
      margin: AppSpacing.marginCard,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
      ),
      child: GestureDetector(
        onTapDown: (_) => setState(() => _isPressed = true),
        onTapUp: (_) {
          setState(() => _isPressed = false);
          widget.onTap();
        },
        onTapCancel: () => setState(() => _isPressed = false),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 120),
          transform: _isPressed
              ? (Matrix4.identity()..translate(0.0, 1.0))
              : Matrix4.identity(),
          child: ListTile(
            leading: Container(
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: AurexColors.olive.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
              ),
              child: Icon(widget.icon, color: AurexColors.olive, size: AppSpacing.iconMd),
            ),
            title: Text(widget.title, style: AurexTypography.bodyLarge),
            subtitle: Text(widget.subtitle, style: AurexTypography.bodyMedium),
            trailing: Icon(
              Icons.chevron_right,
              color: AurexColors.grey,
              size: AppSpacing.iconMd,
            ),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
            ),
          ),
        ),
      ),
    );
  }
}

/// Logout confirmation dialog with loading state
class _LogoutDialog extends ConsumerStatefulWidget {
  final BuildContext parentContext;

  const _LogoutDialog({required this.parentContext});

  @override
  ConsumerState<_LogoutDialog> createState() => _LogoutDialogState();
}

class _LogoutDialogState extends ConsumerState<_LogoutDialog> {
  bool _isLoggingOut = false;

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: AurexColors.charcoal,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radiusXl),
      ),
      contentPadding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.xl, AppSpacing.lg, 0),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: AppSpacing.avatarRadius + 4,
            height: AppSpacing.avatarRadius + 4,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: AurexColors.rust.withValues(alpha: 0.15),
            ),
            child: const Icon(
              Icons.logout_rounded,
              size: AppSpacing.iconLg,
              color: AurexColors.rust,
            ),
          ),
          const SizedBox(height: AppSpacing.lg - 4),
          Text('Konfirmasi Logout', style: AurexTypography.heading2),
          const SizedBox(height: AppSpacing.sm + 4),
          Text(
            'Apakah Anda yakin ingin keluar?\nAnda perlu login kembali untuk menggunakan aplikasi.',
            style: AurexTypography.bodyMedium,
            textAlign: TextAlign.center,
          ),
        ],
      ),
      actionsPadding: const EdgeInsets.fromLTRB(AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.lg - 4),
      actions: [
        SizedBox(
          width: double.infinity,
          child: OutlinedButton(
            onPressed: _isLoggingOut ? null : () => Navigator.pop(context),
            style: OutlinedButton.styleFrom(
              side: BorderSide(color: AurexColors.grey.withValues(alpha: 0.4)),
              padding: AppSpacing.paddingButton,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
              ),
            ),
            child: Text(
              'Batal',
              style: AurexTypography.buttonText.copyWith(color: AurexColors.grey),
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _isLoggingOut ? null : () => _performLogout(),
            icon: _isLoggingOut
                ? const SizedBox(
                    width: AppSpacing.iconSm,
                    height: AppSpacing.iconSm,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: AurexColors.cream,
                    ),
                  )
                : const Icon(Icons.logout_rounded, size: AppSpacing.iconSm),
            label: Text(_isLoggingOut ? 'Logging out...' : 'Logout'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AurexColors.rust,
              foregroundColor: AurexColors.cream,
              padding: AppSpacing.paddingButton,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
              ),
              elevation: 0,
            ),
          ),
        ),
      ],
    );
  }

  Future<void> _performLogout() async {
    setState(() => _isLoggingOut = true);
    await ref.read(authProvider.notifier).logout();
    if (!mounted) return;
    Navigator.pop(context);
    if (widget.parentContext.mounted) {
      widget.parentContext.goNamed('login');
    }
  }
}
