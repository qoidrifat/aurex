import 'package:flutter/material.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';

class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final String? buttonText;
  final VoidCallback? onButtonPressed;

  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    required this.subtitle,
    this.buttonText,
    this.onButtonPressed,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: '$title. $subtitle',
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Semantics(
                label: 'Ikon $title',
                excludeSemantics: true,
                child: Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: AurexColors.olive.withValues(alpha: 0.1),
                  ),
                  child: Icon(
                    icon,
                    size: 64,
                    color: AurexColors.olive.withValues(alpha: 0.6),
                  ),
                ),
              ),
              const SizedBox(height: 32),
              Text(
                title,
                style: AurexTypography.heading2,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),
              Text(
                subtitle,
                style: AurexTypography.bodyMedium,
                textAlign: TextAlign.center,
              ),
              if (buttonText != null && onButtonPressed != null) ...[
                const SizedBox(height: 32),
                ElevatedButton(
                  onPressed: onButtonPressed,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AurexColors.olive,
                    padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: Text(
                    buttonText!,
                    style: AurexTypography.buttonText,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
