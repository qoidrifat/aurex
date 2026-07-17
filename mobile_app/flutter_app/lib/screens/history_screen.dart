import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/typography.dart';
import '../router.dart';
import '../widgets/empty_state.dart';

class HistoryScreen extends StatelessWidget {
  const HistoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Analysis History', style: AurexTypography.heading2),
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: EmptyState(
        icon: Icons.auto_awesome,
        title: 'No Analysis History',
        subtitle:
            'Start your style journey by analyzing your first selfie.\nDiscover your perfect colors and recommended styles!',
        buttonText: 'Start Analysis',
        onButtonPressed: () {
          context.goNamed('upload');
        },
      ),
    );
  }
}
