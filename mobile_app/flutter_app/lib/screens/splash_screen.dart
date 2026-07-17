import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../providers/auth_provider.dart';
import '../router.dart';
import '../widgets/skeleton_loader.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _initializeApp();
  }

  Future<void> _initializeApp() async {
    // Cek onboarding status
    final prefs = await SharedPreferences.getInstance();
    final onboardingCompleted = prefs.getBool('onboarding_completed') ?? false;

    if (!mounted) return;

    // Jika onboarding belum selesai, redirect ke onboarding
    if (!onboardingCompleted) {
      context.goNamed('onboarding');
      return;
    }

    // Tunggu sebentar untuk splash screen display
    await Future.delayed(const Duration(milliseconds: 1500));

    if (!mounted) return;

    // Cek status autentikasi
    await ref.read(authProvider.notifier).checkAuthStatus();

    if (!mounted) return;

    final authState = ref.read(authProvider);
    if (authState.isAuthenticated) {
      context.goNamed('upload');
    } else {
      context.goNamed('login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Logo
            Text(
              'AUREX',
              style: AurexTypography.heading1.copyWith(
                fontSize: 48,
                letterSpacing: 4,
                color: AurexColors.olive,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              'Upgrade Your Look With AI',
              style: AurexTypography.bodyMedium,
            ),
            const SizedBox(height: 48),

            // Shimmer skeleton sebagai loading indicator
            const SkeletonLoader(
              width: 120,
              height: 4,
              borderRadius: 2,
            ),
          ],
        ),
      ),
    );
  }
}
