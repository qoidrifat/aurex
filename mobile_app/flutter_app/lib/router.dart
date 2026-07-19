import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'models/analysis_model.dart';
import 'screens/splash_screen.dart';
import 'screens/onboarding_screen.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'screens/forgot_password_screen.dart';
import 'screens/upload_screen.dart';
import 'screens/analysis_screen.dart';
import 'screens/result_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/history_screen.dart';
import 'screens/settings_screen.dart';
import 'screens/help_screen.dart';
import 'widgets/animations.dart';

/// Defines all route paths as constants to avoid string duplication.
class RoutePaths {
  static const splash = '/splash';
  static const onboarding = '/onboarding';
  static const login = '/login';
  static const register = '/register';
  static const forgotPassword = '/forgot-password';
  static const upload = '/upload';
  static const analyze = '/analyze/:imageId';
  static const result = '/result';
  static const profile = '/profile';
  static const history = '/history';
  static const settings = '/settings';
  static const help = '/help';
}

/// Builds the GoRouter configuration for AUREX.
///
/// Menggunakan custom page transitions (#1 Bulan 3 — Motion Design).
GoRouter buildRouter() {
  return GoRouter(
    initialLocation: RoutePaths.splash,
    routes: [
      GoRoute(
        path: RoutePaths.splash,
        name: 'splash',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const SplashScreen(),
          transitionsBuilder: PageTransitions.fadeThrough,
        ),
      ),
      GoRoute(
        path: RoutePaths.onboarding,
        name: 'onboarding',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const OnboardingScreen(),
          transitionsBuilder: PageTransitions.fadeThrough,
        ),
      ),
      GoRoute(
        path: RoutePaths.login,
        name: 'login',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const LoginScreen(),
          transitionsBuilder: PageTransitions.fadeThrough,
        ),
      ),
      GoRoute(
        path: RoutePaths.register,
        name: 'register',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const RegisterScreen(),
          transitionsBuilder: PageTransitions.fadeThrough,
        ),
      ),
      GoRoute(
        path: RoutePaths.forgotPassword,
        name: 'forgot-password',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const ForgotPasswordScreen(),
          transitionsBuilder: PageTransitions.slideFromBottom,
        ),
      ),
      GoRoute(
        path: RoutePaths.upload,
        name: 'upload',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const UploadScreen(),
          transitionsBuilder: PageTransitions.fadeThrough,
        ),
      ),
      GoRoute(
        path: RoutePaths.analyze,
        name: 'analyze',
        pageBuilder: (context, state) {
          final imageId = int.parse(state.pathParameters['imageId']!);
          return CustomTransitionPage(
            key: state.pageKey,
            child: AnalysisScreen(imageId: imageId),
            transitionsBuilder: PageTransitions.fadeThrough,
          );
        },
      ),
      GoRoute(
        path: RoutePaths.result,
        name: 'result',
        pageBuilder: (context, state) {
          final analysis = state.extra as AnalysisModel;
          return CustomTransitionPage(
            key: state.pageKey,
            child: ResultScreen(analysis: analysis!),
            transitionsBuilder: PageTransitions.slideFromRight,
          );
        },
      ),
      GoRoute(
        path: RoutePaths.profile,
        name: 'profile',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const ProfileScreen(),
          transitionsBuilder: PageTransitions.slideFromRight,
        ),
      ),
      GoRoute(
        path: RoutePaths.history,
        name: 'history',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const HistoryScreen(),
          transitionsBuilder: PageTransitions.slideFromRight,
        ),
      ),
      GoRoute(
        path: RoutePaths.settings,
        name: 'settings',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const SettingsScreen(),
          transitionsBuilder: PageTransitions.slideFromRight,
        ),
      ),
      GoRoute(
        path: RoutePaths.help,
        name: 'help',
        pageBuilder: (context, state) => CustomTransitionPage(
          key: state.pageKey,
          child: const HelpScreen(),
          transitionsBuilder: PageTransitions.slideFromRight,
        ),
      ),
    ],
  );
}
