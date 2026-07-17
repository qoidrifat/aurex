import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
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
          padding: const EdgeInsets.all(24.0),
          child: Column(
            children: [
              CircleAvatar(
                radius: 60,
                backgroundColor: AurexColors.olive,
                child: Text(
                  _getInitials(user?.name ?? 'User'),
                  style: AurexTypography.heading1.copyWith(
                    color: AurexColors.cream,
                    fontSize: 36,
                  ),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                user?.name ?? 'User',
                style: AurexTypography.heading1,
              ),
              const SizedBox(height: 8),
              Text(
                user?.email ?? 'No email',
                style: AurexTypography.bodyMedium,
              ),

              const SizedBox(height: 48),

              Expanded(
                child: Column(
                  children: [
                    _buildMenuTile(
                      context,
                      icon: Icons.auto_awesome,
                      title: 'New Analysis',
                      onTap: () => context.goNamed('upload'),
                    ),
                    _buildMenuTile(
                      context,
                      icon: Icons.history,
                      title: 'Analysis History',
                      onTap: () => context.pushNamed('history'),
                    ),
                    _buildMenuTile(
                      context,
                      icon: Icons.settings,
                      title: 'Settings',
                      onTap: () => context.pushNamed('settings'),
                    ),
                    _buildMenuTile(
                      context,
                      icon: Icons.help_outline,
                      title: 'Help & Support',
                      onTap: () => context.pushNamed('help'),
                    ),
                  ],
                ),
              ),

              SafeArea(
                child: SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () => _confirmLogout(context),
                    icon: const Icon(Icons.logout, color: AurexColors.rust),
                    label: Text(
                      'Logout',
                      style: AurexTypography.buttonText.copyWith(color: AurexColors.rust),
                    ),
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: AurexColors.rust),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
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

  Widget _buildMenuTile(
    BuildContext context, {
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return Card(
      color: AurexColors.charcoal.withValues(alpha: 0.3),
      margin: const EdgeInsets.only(bottom: 8),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        leading: Icon(icon, color: AurexColors.olive),
        title: Text(title, style: AurexTypography.bodyLarge),
        trailing: const Icon(Icons.chevron_right, color: AurexColors.grey),
        onTap: onTap,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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

/// Dialog konfirmasi logout dengan loading state dan animasi
class _LogoutDialog extends ConsumerStatefulWidget {
  final BuildContext parentContext;

  const _LogoutDialog({
    required this.parentContext,
  });

  @override
  ConsumerState<_LogoutDialog> createState() => _LogoutDialogState();
}

class _LogoutDialogState extends ConsumerState<_LogoutDialog> {
  bool _isLoggingOut = false;

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: AurexColors.charcoal,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      contentPadding: const EdgeInsets.fromLTRB(24, 32, 24, 0),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: AurexColors.rust.withValues(alpha: 0.15),
            ),
            child: const Icon(
              Icons.logout_rounded,
              size: 32,
              color: AurexColors.rust,
            ),
          ),
          const SizedBox(height: 20),
          Text(
            'Konfirmasi Logout',
            style: AurexTypography.heading2,
          ),
          const SizedBox(height: 12),
          Text(
            'Apakah Anda yakin ingin keluar?\nAnda perlu login kembali untuk menggunakan aplikasi.',
            style: AurexTypography.bodyMedium,
            textAlign: TextAlign.center,
          ),
        ],
      ),
      actionsPadding: const EdgeInsets.fromLTRB(16, 8, 16, 20),
      actions: [
        SizedBox(
          width: double.infinity,
          child: OutlinedButton(
            onPressed: _isLoggingOut ? null : () => Navigator.pop(context),
            style: OutlinedButton.styleFrom(
              side: BorderSide(color: AurexColors.grey.withValues(alpha: 0.4)),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: Text(
              'Batal',
              style: AurexTypography.buttonText.copyWith(color: AurexColors.grey),
            ),
          ),
        ),
        const SizedBox(height: 8),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _isLoggingOut ? null : () => _performLogout(),
            icon: _isLoggingOut
                ? const SizedBox(
                    width: 18, height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2, color: AurexColors.cream,
                    ),
                  )
                : const Icon(Icons.logout_rounded, size: 18),
            label: Text(_isLoggingOut ? 'Logging out...' : 'Logout'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AurexColors.rust,
              foregroundColor: AurexColors.cream,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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

    Navigator.pop(context); // tutup dialog

    if (widget.parentContext.mounted) {
      widget.parentContext.goNamed('login');
    }
  }
}
