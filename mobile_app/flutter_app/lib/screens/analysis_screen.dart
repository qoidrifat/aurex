import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../models/analysis_model.dart';
import '../router.dart';
import '../services/api_service.dart';
import '../widgets/skeleton_loader.dart';

class AnalysisScreen extends ConsumerStatefulWidget {
  final int imageId;

  const AnalysisScreen({
    super.key,
    required this.imageId,
  });

  @override
  ConsumerState<AnalysisScreen> createState() => _AnalysisScreenState();
}

class _AnalysisScreenState extends ConsumerState<AnalysisScreen> {
  bool _hasError = false;
  String _errorMessage = '';
  bool _isAnalyzing = true;

  @override
  void initState() {
    super.initState();
    _startAnalysis();
  }

  Future<void> _startAnalysis() async {
    setState(() {
      _isAnalyzing = true;
      _hasError = false;
      _errorMessage = '';
    });

    try {
      final apiService = ref.read(apiServiceProvider);
      final response = await apiService.analyze(widget.imageId);
      final analysisData = response.data['analysis'] as Map<String, dynamic>;
      final analysis = AnalysisModel.fromJson(analysisData);

      if (!mounted) return;

      await Future.delayed(const Duration(milliseconds: 800));

      if (!mounted) return;

      context.goNamed('result', extra: analysis);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _isAnalyzing = false;
        _hasError = true;
        _errorMessage = e.message;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isAnalyzing = false;
        _hasError = true;
        _errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _isAnalyzing
          ? const SingleChildScrollView(
              padding: EdgeInsets.all(24),
              child: Column(
                children: [
                  SizedBox(height: 60),
                  SkeletonScoreCircle(),
                  SizedBox(height: 48),
                  SkeletonLoader(
                    width: 150, height: 24, margin: EdgeInsets.only(bottom: 16),
                  ),
                  SkeletonCard(), SkeletonCard(),
                  SizedBox(height: 24),
                  SkeletonLoader(
                    width: 150, height: 24, margin: EdgeInsets.only(bottom: 16),
                  ),
                  SkeletonCard(), SkeletonCard(), SkeletonCard(),
                  SizedBox(height: 32),
                  SkeletonLoader(
                    width: 200, height: 14, borderRadius: 4, margin: EdgeInsets.only(bottom: 40),
                  ),
                ],
              ),
            )
          : Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    if (_hasError) ...[
                      Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: AurexColors.rust.withValues(alpha: 0.1),
                        ),
                        child: const Icon(
                          Icons.error_outline, size: 48, color: AurexColors.rust,
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text('Analysis Failed', style: AurexTypography.heading2),
                      const SizedBox(height: 12),
                      Text(
                        _errorMessage,
                        style: AurexTypography.bodyMedium,
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 32),
                      ElevatedButton.icon(
                        onPressed: _startAnalysis,
                        icon: const Icon(Icons.refresh),
                        label: const Text('Try Again'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AurexColors.olive,
                          padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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
