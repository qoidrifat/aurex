import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../router.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  int _currentPage = 0;

  final List<_OnboardingSlide> _slides = [
    _OnboardingSlide(
      icon: Icons.auto_awesome,
      title: 'Welcome to AUREX',
      description:
          'AI-powered style intelligence that analyzes your features\nand helps you upgrade your look.',
      color: AurexColors.olive,
    ),
    _OnboardingSlide(
      icon: Icons.face_retouching_natural,
      title: 'Smart Face Analysis',
      description:
          'Upload a selfie and our AI will analyze your face shape,\nskin undertone, and facial symmetry with precision.',
      color: AurexColors.rust,
    ),
    _OnboardingSlide(
      icon: Icons.checkroom,
      title: 'Personal Style Guide',
      description:
          'Get personalized recommendations for hairstyles, color\npalettes, and outfits that match your unique features.',
      color: AurexColors.olive,
    ),
  ];

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _completeOnboarding() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('onboarding_completed', true);
    if (mounted) {
      context.goNamed('login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            // Skip button
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 16, 24, 0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Semantics(
                    button: true,
                    label: 'Lewati onboarding',
                    hint: 'Langsung ke halaman login',
                    child: TextButton(
                      onPressed: _completeOnboarding,
                      child: Text(
                        'Skip',
                        style: AurexTypography.bodyLarge.copyWith(
                          color: AurexColors.grey,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // PageView slides
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                onPageChanged: (index) {
                  setState(() => _currentPage = index);
                },
                itemCount: _slides.length,
                itemBuilder: (context, index) {
                  final slide = _slides[index];
                  return Semantics(
                    label: 'Slide ${index + 1}: ${slide.title}. ${slide.description}',
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 40),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            width: 140,
                            height: 140,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: slide.color.withValues(alpha: 0.1),
                            ),
                            child: Icon(
                              slide.icon,
                              size: 72,
                              color: slide.color,
                            ),
                          ),
                          const SizedBox(height: 48),
                          Text(
                            slide.title,
                            style: AurexTypography.heading1,
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            slide.description,
                            style: AurexTypography.bodyMedium,
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),

            // Page indicator + Next/Get Started button
            Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(
                      _slides.length,
                      (index) => AnimatedContainer(
                        duration: const Duration(milliseconds: 300),
                        width: _currentPage == index ? 32 : 10,
                        height: 10,
                        margin: const EdgeInsets.symmetric(horizontal: 4),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(5),
                          color: _currentPage == index
                              ? AurexColors.olive
                              : AurexColors.grey.withValues(alpha: 0.3),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () {
                        if (_currentPage < _slides.length - 1) {
                          _pageController.nextPage(
                            duration: const Duration(milliseconds: 400),
                            curve: Curves.easeInOut,
                          );
                        } else {
                          _completeOnboarding();
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AurexColors.olive,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(
                        _currentPage < _slides.length - 1 ? 'Next' : 'Get Started',
                        style: AurexTypography.buttonText,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OnboardingSlide {
  final IconData icon;
  final String title;
  final String description;
  final Color color;

  const _OnboardingSlide({
    required this.icon,
    required this.title,
    required this.description,
    required this.color,
  });
}
