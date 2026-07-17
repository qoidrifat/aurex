import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/colors.dart';
import '../providers/theme_provider.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Settings',
          style: TextStyle(
            color: theme.colorScheme.onSurface,
            fontSize: 24,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: theme.colorScheme.onSurface),
          onPressed: () => context.pop(),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSectionHeader(context, 'Tampilan', Icons.palette_outlined),
          const SizedBox(height: 8),

          // Dark Mode Toggle
          Card(
            color: theme.colorScheme.surface,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            child: SwitchListTile(
              secondary: Icon(
                isDarkMode ? Icons.dark_mode : Icons.light_mode,
                color: AurexColors.rust,
              ),
              title: Text(
                'Dark Mode',
                style: TextStyle(
                  color: theme.colorScheme.onSurface,
                  fontWeight: FontWeight.w500,
                ),
              ),
              subtitle: Text(
                isDarkMode ? 'Tampilan gelap aktif' : 'Tampilan terang aktif',
                style: TextStyle(
                  color: theme.colorScheme.onSurface.withValues(alpha: 0.6),
                  fontSize: 13,
                ),
              ),
              value: isDarkMode,
              trackColor: WidgetStateProperty.resolveWith((states) {
                if (states.contains(WidgetState.selected)) {
                  return AurexColors.rust.withValues(alpha: 0.6);
                }
                return null;
              }),
              thumbColor: WidgetStateProperty.resolveWith((states) {
                if (states.contains(WidgetState.selected)) {
                  return AurexColors.rust;
                }
                return null;
              }),
              onChanged: (_) {
                ref.read(isDarkModeProvider.notifier).toggle();
              },
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
          ),

          const SizedBox(height: 32),

          _buildSectionHeader(context, 'Tentang', Icons.info_outline),
          const SizedBox(height: 8),

          Card(
            color: theme.colorScheme.surface,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.info, color: AurexColors.olive),
                  title: Text(
                    'Versi Aplikasi',
                    style: TextStyle(color: theme.colorScheme.onSurface),
                  ),
                  trailing: Text(
                    '1.0.0',
                    style: TextStyle(color: theme.colorScheme.onSurface.withValues(alpha: 0.6)),
                  ),
                ),
                const Divider(height: 1, indent: 16, endIndent: 16),
                ListTile(
                  leading: const Icon(Icons.star, color: AurexColors.olive),
                  title: Text(
                    'Berikan Rating',
                    style: TextStyle(color: theme.colorScheme.onSurface),
                  ),
                  trailing: Icon(
                    Icons.chevron_right,
                    color: theme.colorScheme.onSurface.withValues(alpha: 0.4),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 32),

          _buildSectionHeader(context, 'Akun', Icons.person_outline),
          const SizedBox(height: 8),

          Card(
            color: theme.colorScheme.surface,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.email_outlined, color: AurexColors.olive),
                  title: Text(
                    'Verifikasi Email',
                    style: TextStyle(color: theme.colorScheme.onSurface),
                  ),
                  subtitle: Text(
                    'Kirim ulang email verifikasi',
                    style: TextStyle(
                      color: theme.colorScheme.onSurface.withValues(alpha: 0.6),
                      fontSize: 13,
                    ),
                  ),
                  trailing: Icon(
                    Icons.chevron_right,
                    color: theme.colorScheme.onSurface.withValues(alpha: 0.4),
                  ),
                ),
                const Divider(height: 1, indent: 16, endIndent: 16),
                ListTile(
                  leading: const Icon(Icons.lock_outline, color: AurexColors.olive),
                  title: Text(
                    'Ubah Password',
                    style: TextStyle(color: theme.colorScheme.onSurface),
                  ),
                  trailing: Icon(
                    Icons.chevron_right,
                    color: theme.colorScheme.onSurface.withValues(alpha: 0.4),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 40),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(BuildContext context, String title, IconData icon) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 4),
      child: Row(
        children: [
          Icon(icon, size: 18, color: AurexColors.rust),
          const SizedBox(width: 8),
          Text(
            title,
            style: TextStyle(
              color: theme.colorScheme.onSurface.withValues(alpha: 0.7),
              fontSize: 14,
              fontWeight: FontWeight.w600,
              letterSpacing: 0.5,
            ),
          ),
        ],
      ),
    );
  }
}
