import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../core/theme/spacing.dart';
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
              onPressed: () => context.pushNamed('profile'),
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Animated Style Score ──────────────────
            Semantics(
              label: 'Style score: ${analysis.styleScore}',
              child: Center(
                child: _AnimatedScoreCircle(score: analysis.styleScore),
              ),
            ),
            const SizedBox(height: AppSpacing.xxl),

            // ── Face Analysis Section ────────────────
            Row(
              children: [
                const Icon(Icons.face, color: AurexColors.olive, size: AppSpacing.iconMd),
                const SizedBox(width: AppSpacing.sm),
                Text('Face Analysis', style: AurexTypography.heading2),
              ],
            ),
            const SizedBox(height: AppSpacing.md),

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

            const SizedBox(height: AppSpacing.xl),

            // ── Recommendations Section ──────────────
            Row(
              children: [
                const Icon(Icons.auto_awesome, color: AurexColors.olive, size: AppSpacing.iconMd),
                const SizedBox(width: AppSpacing.sm),
                Text('Recommendations', style: AurexTypography.heading2),
              ],
            ),
            const SizedBox(height: AppSpacing.md),

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

            const SizedBox(height: AppSpacing.xl),

            // ── Action Buttons ───────────────────────
            Center(
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => context.goNamed('upload'),
                  icon: const Icon(Icons.refresh),
                  label: const Text('New Analysis'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AurexColors.olive,
                    padding: AppSpacing.paddingButton,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                    ),
                    elevation: 0,
                  ),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
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

/// Animated circular score reveal with stroke animation.
class _AnimatedScoreCircle extends StatefulWidget {
  final int score;
  const _AnimatedScoreCircle({required this.score});

  @override
  State<_AnimatedScoreCircle> createState() => _AnimatedScoreCircleState();
}

class _AnimatedScoreCircleState extends State<_AnimatedScoreCircle>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scoreAnimation;
  late Animation<double> _circleAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    _scoreAnimation = CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.3, 0.8, curve: Curves.easeOutBack),
    );
    _circleAnimation = CurvedAnimation(
      parent: _controller,
      curve: const Interval(0.0, 0.7, curve: Curves.easeOutCubic),
    );
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final disableAnimations = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    if (disableAnimations) {
      return _buildStaticScore();
    }

    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        return Container(
          padding: const EdgeInsets.all(AppSpacing.xl),
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(
              color: AurexColors.olive.withValues(alpha: 0.3),
              width: AppSpacing.scoreCircleBorderWidth,
            ),
            color: AurexColors.olive.withValues(alpha: 0.05),
          ),
          child: Stack(
            alignment: Alignment.center,
            children: [
              // Progress ring
              SizedBox(
                width: AppSpacing.scoreCircleSize,
                height: AppSpacing.scoreCircleSize,
                child: CustomPaint(
                  painter: _CircleProgressPainter(
                    progress: _circleAnimation.value,
                    color: AurexColors.olive,
                    backgroundColor: AurexColors.olive.withValues(alpha: 0.1),
                  ),
                ),
              ),
              // Score text
              Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    '${(_scoreAnimation.value * widget.score).round()}',
                    style: AurexTypography.heading1.copyWith(fontSize: AppSpacing.scoreTextSize),
                  ),
                  Text('Style Score', style: AurexTypography.bodyMedium),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildStaticScore() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(
          color: AurexColors.olive,
          width: AppSpacing.scoreCircleBorderWidth,
        ),
        color: AurexColors.olive.withValues(alpha: 0.05),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '${widget.score}',
            style: AurexTypography.heading1.copyWith(fontSize: AppSpacing.scoreTextSize),
          ),
          Text('Style Score', style: AurexTypography.bodyMedium),
        ],
      ),
    );
  }
}

/// Custom painter untuk circular progress ring.
class _CircleProgressPainter extends CustomPainter {
  final double progress;
  final Color color;
  final Color backgroundColor;
  final double strokeWidth;

  _CircleProgressPainter({
    required this.progress,
    required this.color,
    required this.backgroundColor,
    this.strokeWidth = 6,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = (size.width - strokeWidth) / 2;

    // Background circle
    final bgPaint = Paint()
      ..color = backgroundColor
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;

    canvas.drawCircle(center, radius, bgPaint);

    // Progress arc
    final progressPaint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;

    final sweepAngle = 2 * math.pi * progress;
    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius),
      -math.pi / 2, // Start from top
      sweepAngle,
      false,
      progressPaint,
    );
  }

  @override
  bool shouldRepaint(covariant _CircleProgressPainter oldDelegate) =>
      oldDelegate.progress != progress;
}
