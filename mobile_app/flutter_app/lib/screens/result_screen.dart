import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../models/analysis_model.dart';
import '../router.dart';
import '../widgets/style_card.dart';

class ResultScreen extends StatelessWidget {
  final AnalysisModel analysis;

  const ResultScreen({
    super.key,
    required this.analysis,
  });

  @override
  Widget build(BuildContext context) {
    final recommendation = analysis.recommendation;

    return Scaffold(
      appBar: AppBar(
        title: Text('Your Style Result', style: AurexTypography.heading2),
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          Semantics(
            button: true,
            label: 'Profil',
            hint: 'Buka halaman profil',
            child: IconButton(
              icon: const Icon(Icons.person_outline),
              onPressed: () {
                context.pushNamed('profile');
              },
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Style Score Circle
            Semantics(
              label: 'Style score: ${analysis.styleScore}',
              child: Center(
                child: Container(
                  padding: const EdgeInsets.all(32),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: AurexColors.olive, width: 4),
                    color: AurexColors.olive.withValues(alpha: 0.05),
                  ),
                  child: Column(
                    children: [
                      Text(
                        '${analysis.styleScore}',
                        style: AurexTypography.heading1.copyWith(fontSize: 64),
                      ),
                      Text(
                        'Style Score',
                        style: AurexTypography.bodyMedium,
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 48),

            // Face Analysis Section
            Row(
              children: [
                const Icon(Icons.face, color: AurexColors.olive, size: 24),
                const SizedBox(width: 8),
                Text('Face Analysis', style: AurexTypography.heading2),
              ],
            ),
            const SizedBox(height: 16),

            StyleCard(
              title: 'Face Shape',
              value: _capitalize(analysis.faceShape),
              icon: Icons.face,
            ),
            StyleCard(
              title: 'Skin Undertone',
              value: _capitalize(analysis.undertone),
              icon: Icons.palette,
            ),

            const SizedBox(height: 32),

            // Recommendations Section
            Row(
              children: [
                const Icon(Icons.auto_awesome, color: AurexColors.olive, size: 24),
                const SizedBox(width: 8),
                Text('Recommendations', style: AurexTypography.heading2),
              ],
            ),
            const SizedBox(height: 16),

            StyleCard(
              title: 'Best Hairstyles',
              value: recommendation.hairstyles
                  .map((h) => _capitalize(h))
                  .join(', '),
              icon: Icons.content_cut,
            ),
            StyleCard(
              title: 'Best Colors',
              value: recommendation.colorPalette
                  .map((c) => _capitalize(c))
                  .join(', '),
              icon: Icons.color_lens,
            ),
            StyleCard(
              title: 'Outfit Ideas',
              value: recommendation.outfit
                  .map((o) => _capitalize(o))
                  .join(', '),
              icon: Icons.checkroom,
            ),

            const SizedBox(height: 32),

            // Action Buttons
            Center(
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () {
                    context.goNamed('upload');
                  },
                  icon: const Icon(Icons.refresh),
                  label: const Text('New Analysis'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AurexColors.olive,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  String _capitalize(String text) {
    if (text.isEmpty) return text;
    return text[0].toUpperCase() + text.substring(1);
  }
}
