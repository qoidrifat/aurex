import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../providers/auth_provider.dart';
import '../widgets/primary_button.dart';

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _emailFocusNode = FocusNode();
  bool _isLoading = false;
  bool _isSuccess = false;
  String? _message;

  @override
  void dispose() {
    _emailController.dispose();
    _emailFocusNode.dispose();
    super.dispose();
  }

  Future<void> _handleForgotPassword() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _message = null;
    });

    final result = await ref.read(authProvider.notifier).forgotPassword(
          _emailController.text.trim(),
        );

    if (!mounted) return;

    setState(() {
      _isLoading = false;
      if (result != null && !result.contains('telah dikirim')) {
        _message = result;
        _isSuccess = false;
      } else {
        _isSuccess = true;
        _message = result ?? 'Link reset password telah dikirim ke email Anda.';
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 40),

                // Success State
                if (_isSuccess) ...[
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: AurexColors.olive.withValues(alpha: 0.1),
                    ),
                    child: const Icon(
                      Icons.check_circle_outline,
                      size: 48,
                      color: AurexColors.olive,
                    ),
                  ),
                  const SizedBox(height: 32),
                  Text(
                    'Email Terkirim!',
                    style: AurexTypography.heading1,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _message ?? 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.',
                    style: AurexTypography.bodyMedium,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 48),
                  PrimaryButton(
                    text: 'Kembali ke Login',
                    onPressed: () => context.pop(),
                  ),
                ],

                // Form State
                if (!_isSuccess) ...[
                  Text(
                    'Lupa Password',
                    style: AurexTypography.heading1,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Masukkan email Anda dan kami akan mengirimkan link untuk mereset password.',
                    style: AurexTypography.bodyMedium,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 40),

                  if (_message != null)
                    Container(
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: AurexColors.rust.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AurexColors.rust.withValues(alpha: 0.3)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline, color: AurexColors.rust, size: 20),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              _message!,
                              style: AurexTypography.bodyMedium.copyWith(color: AurexColors.rust),
                            ),
                          ),
                        ],
                      ),
                    ),

                  TextFormField(
                    controller: _emailController,
                    focusNode: _emailFocusNode,
                    keyboardType: TextInputType.emailAddress,
                    textInputAction: TextInputAction.done,
                    onFieldSubmitted: (_) => _handleForgotPassword(),
                    decoration: InputDecoration(
                      labelText: 'Email',
                      hintText: 'Enter your registered email',
                      filled: true,
                      fillColor: AurexColors.charcoal.withValues(alpha: 0.5),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(color: AurexColors.grey.withValues(alpha: 0.3)),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: const BorderSide(color: AurexColors.olive, width: 2),
                      ),
                      prefixIcon: const Icon(Icons.email_outlined, color: AurexColors.grey),
                    ),
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Email tidak boleh kosong';
                      }
                      if (!RegExp(r'^[^@]+@[^@]+\.[^@]+$').hasMatch(value.trim())) {
                        return 'Format email tidak valid';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 24),
                  PrimaryButton(
                    text: 'Kirim Link Reset',
                    isLoading: _isLoading,
                    onPressed: _handleForgotPassword,
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
