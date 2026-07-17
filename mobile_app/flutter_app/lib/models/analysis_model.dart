class AnalysisModel {
  final int id;
  final String faceShape;
  final String undertone;
  final int styleScore;
  final RecommendationModel recommendation;
  final DateTime createdAt;

  AnalysisModel({
    required this.id,
    required this.faceShape,
    required this.undertone,
    required this.styleScore,
    required this.recommendation,
    required this.createdAt,
  });

  factory AnalysisModel.fromJson(Map<String, dynamic> json) {
    return AnalysisModel(
      id: json['id'],
      faceShape: json['face_shape'],
      undertone: json['undertone'],
      styleScore: json['style_score'],
      recommendation: RecommendationModel.fromJson(json['recommendation']),
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : DateTime.now(),
    );
  }
}

class RecommendationModel {
  final List<String> hairstyles;
  final List<String> colorPalette;
  final List<String> outfit;

  RecommendationModel({
    required this.hairstyles,
    required this.colorPalette,
    required this.outfit,
  });

  factory RecommendationModel.fromJson(Map<String, dynamic> json) {
    return RecommendationModel(
      hairstyles: List<String>.from(json['hairstyle']),
      colorPalette: List<String>.from(json['color_palette']),
      outfit: List<String>.from(json['outfit']),
    );
  }
}
