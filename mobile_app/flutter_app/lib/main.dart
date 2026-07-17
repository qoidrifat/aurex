import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:sentry_flutter/sentry_flutter.dart';
import 'package:go_router/go_router.dart';
import 'core/theme/colors.dart';
import 'providers/theme_provider.dart';
import 'router.dart';
import 'screens/splash_screen.dart';

Future<void> main() async {
  await SentryFlutter.init(
    (options) {
      options.dsn = const String.fromEnvironment('SENTRY_DSN', defaultValue: '');
      options.tracesSampleRate = 0.2;
    },
    appRunner: () => runApp(
      const ProviderScope(
        child: AurexApp(),
      ),
    ),
  );
}

/// Global router instance — dibuat sekali agar navigation state tidak hilang saat rebuild.
final _router = buildRouter();

class AurexApp extends ConsumerWidget {
  const AurexApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDarkMode = ref.watch(isDarkModeProvider);

    return MaterialApp.router(
      title: 'AUREX',
      debugShowCheckedModeBanner: false,
      theme: _buildTheme(Brightness.light),
      darkTheme: _buildTheme(Brightness.dark),
      themeMode: isDarkMode ? ThemeMode.dark : ThemeMode.light,
      routerConfig: _router,
    );
  }

  ThemeData _buildTheme(Brightness brightness) {
    final isDark = brightness == Brightness.dark;
    final Color scaffoldBg = isDark ? AurexColors.charcoal : const Color(0xFFF8F6F0);
    final Color surface = isDark ? AurexColors.charcoal : const Color(0xFFEFECE4);
    final Color textPrimary = isDark ? AurexColors.cream : const Color(0xFF2C2C2C);
    final Color textSecondary = isDark ? AurexColors.grey : const Color(0xFF6B6B6B);

    return ThemeData(
      brightness: brightness,
      scaffoldBackgroundColor: scaffoldBg,
      primaryColor: AurexColors.olive,
      colorScheme: brightness == Brightness.dark
          ? const ColorScheme.dark(
              primary: AurexColors.olive,
              secondary: AurexColors.rust,
              surface: AurexColors.charcoal,
            )
          : ColorScheme.light(
              primary: AurexColors.olive,
              secondary: AurexColors.rust,
              surface: surface,
              onPrimary: Colors.white,
              onSecondary: Colors.white,
              onSurface: textPrimary,
            ),
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.transparent,
        elevation: 0,
        titleTextStyle: TextStyle(
          color: textPrimary,
          fontSize: 24,
          fontWeight: FontWeight.bold,
        ),
        iconTheme: IconThemeData(color: textPrimary),
      ),
      cardTheme: CardThemeData(
        color: isDark ? AurexColors.charcoal.withValues(alpha: 0.3) : surface,
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
      textTheme: TextTheme(
        headlineLarge: TextStyle(
          color: textPrimary,
          fontSize: 32,
          fontWeight: FontWeight.bold,
        ),
        headlineMedium: TextStyle(
          color: textPrimary,
          fontSize: 24,
          fontWeight: FontWeight.bold,
        ),
        bodyLarge: TextStyle(color: textPrimary, fontSize: 18),
        bodyMedium: TextStyle(color: textSecondary, fontSize: 16),
        labelLarge: TextStyle(
          color: textPrimary,
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
      useMaterial3: true,
      // --- Focus / Keyboard Accessibility ---
      focusColor: AurexColors.olive.withValues(alpha: 0.25),
    );
  }
}
