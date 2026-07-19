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
    final Color scaffoldBg = isDark ? AurexColors.charcoal : AurexColors.surfaceLight;
    final Color surface = isDark ? AurexColors.cardDark : AurexColors.cardLight;
    final Color textPrimary = isDark ? AurexColors.textOnDark : AurexColors.textOnLight;
    final Color textSecondary = isDark ? AurexColors.greyDark : AurexColors.grey;

    // ── WCAG AA Color Contrast Fix (#3 Prioritas Tinggi) ──
    // Gunakan oliveLight (#7B9346) untuk aksesibilitas di dark mode
    // Olive asli (#556B2F) hanya 2.5:1 vs charcoal — GAGAL WCAG AA
    final Color primaryColor = isDark ? AurexColors.oliveLight : AurexColors.olive;
    final Color onPrimaryColor = isDark ? AurexColors.charcoal : Colors.white;
    final Color focusColor = isDark
        ? AurexColors.oliveLight.withValues(alpha: 0.35)
        : AurexColors.olive.withValues(alpha: 0.25);

    return ThemeData(
      brightness: brightness,
      scaffoldBackgroundColor: scaffoldBg,
      primaryColor: primaryColor,
      colorScheme: isDark
          ? ColorScheme.dark(
              primary: primaryColor,
              onPrimary: onPrimaryColor,
              secondary: AurexColors.rust,
              surface: AurexColors.charcoal,
              onSurface: textPrimary,
            )
          : ColorScheme.light(
              primary: primaryColor,
              onPrimary: onPrimaryColor,
              secondary: AurexColors.rust,
              surface: surface,
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
        color: isDark ? AurexColors.cardDark : surface,
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
      focusColor: focusColor,
    );
  }
}
