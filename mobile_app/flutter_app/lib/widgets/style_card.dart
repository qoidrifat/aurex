import 'package:flutter/material.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';

class StyleCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;

  const StyleCard({
    super.key,
    required this.title,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: '$title: $value',
      child: Container(
        margin: const EdgeInsets.only(bottom: 16),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: AurexColors.charcoal.withValues(alpha: 0.5),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AurexColors.olive.withValues(alpha: 0.3)),
        ),
        child: Row(
          children: [
            Semantics(
              label: 'Ikon $title',
              excludeSemantics: true,
              child: Icon(icon, color: AurexColors.olive, size: 32),
            ),
            const SizedBox(width: 20),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: AurexTypography.bodyMedium),
                  const SizedBox(height: 4),
                  Text(value, style: AurexTypography.bodyLarge.copyWith(fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
