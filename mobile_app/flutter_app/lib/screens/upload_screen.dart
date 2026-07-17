import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';
import '../router.dart';
import '../services/api_service.dart';
import '../services/image_optimizer.dart';
import '../widgets/primary_button.dart';
import '../widgets/skeleton_loader.dart';

class UploadScreen extends ConsumerStatefulWidget {
  const UploadScreen({super.key});

  @override
  ConsumerState<UploadScreen> createState() => _UploadScreenState();
}

class _UploadScreenState extends ConsumerState<UploadScreen> {
  final ImagePicker _picker = ImagePicker();
  File? _selectedImage;
  bool _isUploading = false;
  String? _errorMessage;

  Future<void> _pickImage(ImageSource source) async {
    setState(() {
      _errorMessage = null;
    });

    try {
      final XFile? pickedFile = await _picker.pickImage(
        source: source,
        maxWidth: 2048,
        maxHeight: 2048,
        imageQuality: 90,
      );

      if (pickedFile != null) {
        // Optimasi gambar: kompresi & resize sebelum upload
        final optimizedPath = await ImageOptimizer.optimize(
          sourcePath: pickedFile.path,
          quality: 70,
          maxWidth: 1024,
          maxHeight: 1024,
        );

        setState(() {
          _selectedImage = File(optimizedPath);
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Gagal mengambil foto. Silakan coba lagi.';
      });
    }
  }

  Future<void> _uploadAndAnalyze() async {
    if (_selectedImage == null) return;

    setState(() {
      _isUploading = true;
      _errorMessage = null;
    });

    try {
      final apiService = ref.read(apiServiceProvider);
      final uploadResponse = await apiService.uploadSelfie(_selectedImage!.path);
      final imageId = uploadResponse.data['image']['id'] as int;

      if (!mounted) return;

      context.goNamed('analyze', pathParameters: {'imageId': imageId.toString()});
    } on ApiException catch (e) {
      setState(() {
        _isUploading = false;
        _errorMessage = e.message;
      });
    } catch (e) {
      setState(() {
        _isUploading = false;
        _errorMessage = 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Upload Selfie', style: AurexTypography.heading2),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (_isUploading)
                const _UploadSkeleton()
              else ...[
                GestureDetector(
                  onTap: _showImageSourceDialog,
                  child: Container(
                    height: 300,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: AurexColors.charcoal.withValues(alpha: 0.5),
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(
                        color: _selectedImage != null
                            ? AurexColors.olive
                            : AurexColors.grey.withValues(alpha: 0.3),
                        width: 2,
                      ),
                      image: _selectedImage != null
                          ? DecorationImage(
                              image: FileImage(_selectedImage!),
                              fit: BoxFit.cover,
                            )
                          : null,
                    ),
                    child: _selectedImage == null
                        ? Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.camera_alt, size: 64, color: AurexColors.olive),
                              const SizedBox(height: 16),
                              Text(
                                'Take or Upload a Photo',
                                style: AurexTypography.bodyLarge,
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Tap to select',
                                style: AurexTypography.bodyMedium,
                              ),
                            ],
                          )
                        : null,
                  ),
                ),

                if (_errorMessage != null) ...[
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
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
                            _errorMessage!,
                            style: AurexTypography.bodyMedium.copyWith(color: AurexColors.rust),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 48),

                PrimaryButton(
                  text: 'Take Photo',
                  isLoading: _isUploading,
                  onPressed: () => _pickImage(ImageSource.camera),
                ),
                const SizedBox(height: 16),

                PrimaryButton(
                  text: 'Upload from Gallery',
                  color: AurexColors.rust,
                  isLoading: _isUploading,
                  onPressed: () => _pickImage(ImageSource.gallery),
                ),

                if (_selectedImage != null) ...[
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _isUploading ? null : _uploadAndAnalyze,
                      icon: const Icon(Icons.auto_awesome),
                      label: Text(
                        _isUploading ? 'Uploading...' : 'Analyze My Style',
                        style: AurexTypography.buttonText,
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AurexColors.olive,
                        disabledBackgroundColor: AurexColors.olive.withValues(alpha: 0.6),
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                    ),
                  ),
                ],
              ],
            ],
          ),
        ),
      ),
    );
  }

  void _showImageSourceDialog() {
    showModalBottomSheet(
      context: context,
      backgroundColor: AurexColors.charcoal,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AurexColors.grey.withValues(alpha: 0.3),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'Choose Photo Source',
                style: AurexTypography.heading2,
              ),
              const SizedBox(height: 24),
              ListTile(
                leading: const Icon(Icons.camera_alt, color: AurexColors.olive),
                title: Text('Camera', style: AurexTypography.bodyLarge),
                onTap: () {
                  Navigator.pop(context);
                  _pickImage(ImageSource.camera);
                },
              ),
              ListTile(
                leading: const Icon(Icons.photo_library, color: AurexColors.olive),
                title: Text('Gallery', style: AurexTypography.bodyLarge),
                onTap: () {
                  Navigator.pop(context);
                  _pickImage(ImageSource.gallery);
                },
              ),
              if (_selectedImage != null)
                ListTile(
                  leading: const Icon(Icons.delete_outline, color: AurexColors.rust),
                  title: Text('Remove Photo', style: AurexTypography.bodyLarge),
                  onTap: () {
                    Navigator.pop(context);
                    setState(() {
                      _selectedImage = null;
                    });
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Skeleton shimmer widget yang ditampilkan selama proses upload
class _UploadSkeleton extends StatelessWidget {
  const _UploadSkeleton();

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const SkeletonLoader(height: 300, borderRadius: 24),
        const SizedBox(height: 48),
        const SkeletonLoader(
          height: 52, borderRadius: 12, margin: EdgeInsets.only(bottom: 16),
        ),
        const SkeletonLoader(height: 52, borderRadius: 12),
      ],
    );
  }
}
