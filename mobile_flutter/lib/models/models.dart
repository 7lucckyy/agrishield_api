typedef Json = Map<String, dynamic>;

class Organization {
  const Organization({
    required this.id,
    required this.name,
    this.role,
    this.clusterName,
  });
  factory Organization.fromJson(Json json) => Organization(
    id: (json['id'] as num).toInt(),
    name: json['name']?.toString() ?? '',
    role: json['role']?.toString(),
    clusterName: json['cluster_name']?.toString(),
  );
  final int id;
  final String name;
  final String? role;
  final String? clusterName;
}

class UserProfile {
  const UserProfile({
    required this.id,
    required this.name,
    this.phone,
    this.locale = 'en',
    this.organizations = const [],
  });
  factory UserProfile.fromJson(Json json) => UserProfile(
    id: (json['id'] as num).toInt(),
    name: json['name']?.toString() ?? '',
    phone: json['phone']?.toString(),
    locale: json['locale']?.toString() ?? 'en',
    organizations: (json['organizations'] as List? ?? [])
        .whereType<Json>()
        .map(Organization.fromJson)
        .toList(),
  );
  final int id;
  final String name;
  final String? phone;
  final String locale;
  final List<Organization> organizations;

  Json toJson() => {
    'id': id,
    'name': name,
    'phone': phone,
    'locale': locale,
    'organizations': organizations
        .map(
          (organization) => {
            'id': organization.id,
            'name': organization.name,
            'role': organization.role,
            'cluster_name': organization.clusterName,
          },
        )
        .toList(),
  };
}

class AuthSession {
  const AuthSession({
    required this.user,
    required this.organizations,
    required this.token,
  });
  factory AuthSession.fromJson(Json json) => AuthSession(
    user: UserProfile.fromJson(json['user'] as Json),
    organizations: (json['organizations'] as List? ?? [])
        .whereType<Json>()
        .map(Organization.fromJson)
        .toList(),
    token: json['token'].toString(),
  );
  final UserProfile user;
  final List<Organization> organizations;
  final String token;
}

class CropCycle {
  const CropCycle({required this.status, this.cropName, this.plantingDate});
  factory CropCycle.fromJson(Json json) => CropCycle(
    status: json['status']?.toString() ?? '',
    cropName: (json['crop'] as Json?)?['name']?.toString(),
    plantingDate: json['planting_date']?.toString(),
  );
  final String status;
  final String? cropName;
  final String? plantingDate;
  Json toJson() => {
    'status': status,
    'crop': cropName == null ? null : {'name': cropName},
    'planting_date': plantingDate,
  };
}

class Crop {
  const Crop({required this.id, required this.name, this.code, this.category});

  factory Crop.fromJson(Json json) => Crop(
    id: (json['id'] as num).toInt(),
    name: json['name']?.toString() ?? '',
    code: json['code']?.toString(),
    category: json['category']?.toString(),
  );

  final int id;
  final String name;
  final String? code;
  final String? category;
  Json toJson() => {'id': id, 'name': name, 'code': code, 'category': category};
}

class FarmSection {
  const FarmSection({
    required this.id,
    required this.name,
    required this.crop,
    required this.hectares,
    this.acres,
    this.farmPercentage,
    this.position,
    this.notes,
    this.boundaryGeoJson,
  });

  factory FarmSection.fromJson(Json json) {
    final area = json['area'] as Json? ?? const <String, dynamic>{};

    return FarmSection(
      id: (json['id'] as num).toInt(),
      name: json['name']?.toString() ?? '',
      crop: Crop.fromJson(json['crop'] as Json),
      hectares: (area['hectares'] as num).toDouble(),
      acres: (area['acres'] as num?)?.toDouble(),
      farmPercentage: (area['farm_percentage'] as num?)?.toDouble(),
      position: (json['position'] as num?)?.toInt(),
      notes: json['notes']?.toString(),
      boundaryGeoJson: json['boundary_geojson'] is Json
          ? json['boundary_geojson'] as Json
          : null,
    );
  }

  final int id;
  final String name;
  final Crop crop;
  final double hectares;
  final double? acres;
  final double? farmPercentage;
  final int? position;
  final String? notes;
  final Json? boundaryGeoJson;
  Json toJson() => {
    'id': id,
    'name': name,
    'crop': crop.toJson(),
    'area': {
      'hectares': hectares,
      'acres': acres,
      'farm_percentage': farmPercentage,
    },
    'position': position,
    'notes': notes,
    'boundary_geojson': boundaryGeoJson,
  };
}

class FarmSectionSummary {
  const FarmSectionSummary({
    required this.count,
    required this.allocatedHectares,
    this.remainingHectares,
  });

  factory FarmSectionSummary.fromJson(Json json) => FarmSectionSummary(
    count: (json['count'] as num?)?.toInt() ?? 0,
    allocatedHectares: (json['allocated_hectares'] as num?)?.toDouble() ?? 0,
    remainingHectares: (json['remaining_hectares'] as num?)?.toDouble(),
  );

  final int count;
  final double allocatedHectares;
  final double? remainingHectares;
  Json toJson() => {
    'count': count,
    'allocated_hectares': allocatedHectares,
    'remaining_hectares': remainingHectares,
  };
}

class Farm {
  const Farm({
    required this.id,
    required this.name,
    required this.status,
    this.locality,
    this.state,
    this.hectares,
    this.latitude,
    this.longitude,
    this.boundaryGeoJson,
    this.activeCropCycle,
    this.sections = const [],
    this.sectionSummary,
    this.sectionsCount,
    this.lastSyncedAt,
  });
  factory Farm.fromJson(Json json) => Farm(
    id: json['id'].toString(),
    name: json['name']?.toString() ?? '',
    status: json['status']?.toString() ?? 'active',
    locality: json['locality']?.toString(),
    state: json['state']?.toString(),
    hectares: ((json['area'] as Json?)?['hectares'] as num?)?.toDouble(),
    latitude: ((json['centroid'] as Json?)?['latitude'] as num?)?.toDouble(),
    longitude: ((json['centroid'] as Json?)?['longitude'] as num?)?.toDouble(),
    boundaryGeoJson: json['boundary_geojson'] is Json
        ? json['boundary_geojson'] as Json
        : null,
    activeCropCycle: json['active_crop_cycle'] is Json
        ? CropCycle.fromJson(json['active_crop_cycle'] as Json)
        : null,
    sections: (json['sections'] as List? ?? [])
        .whereType<Json>()
        .map(FarmSection.fromJson)
        .toList(),
    sectionSummary: json['section_summary'] is Json
        ? FarmSectionSummary.fromJson(json['section_summary'] as Json)
        : null,
    sectionsCount: (json['sections_count'] as num?)?.toInt(),
    lastSyncedAt: json['last_synced_at']?.toString(),
  );
  final String id;
  final String name;
  final String status;
  final String? locality;
  final String? state;
  final double? hectares;
  final double? latitude;
  final double? longitude;
  final Json? boundaryGeoJson;
  final CropCycle? activeCropCycle;
  final List<FarmSection> sections;
  final FarmSectionSummary? sectionSummary;
  final int? sectionsCount;
  final String? lastSyncedAt;
  Json toJson() => {
    'id': id,
    'name': name,
    'status': status,
    'locality': locality,
    'state': state,
    'area': {'hectares': hectares},
    'centroid': {'latitude': latitude, 'longitude': longitude},
    'boundary_geojson': boundaryGeoJson,
    'active_crop_cycle': activeCropCycle?.toJson(),
    'sections': sections.map((section) => section.toJson()).toList(),
    'section_summary': sectionSummary?.toJson(),
    'sections_count': sectionsCount,
    'last_synced_at': lastSyncedAt,
  };
}

class WeatherDay {
  const WeatherDay({
    required this.date,
    this.minimum,
    this.maximum,
    this.rainfall,
    this.rainProbability,
    this.condition,
  });
  factory WeatherDay.fromJson(Json json) => WeatherDay(
    date: json['forecast_date']?.toString() ?? '',
    minimum: (json['temperature_min'] as num?)?.toDouble(),
    maximum: (json['temperature_max'] as num?)?.toDouble(),
    rainfall: (json['rainfall'] as num?)?.toDouble(),
    rainProbability: (json['rainfall_probability'] as num?)?.toDouble(),
    condition: json['condition_code']?.toString(),
  );
  final String date;
  final double? minimum;
  final double? maximum;
  final double? rainfall;
  final double? rainProbability;
  final String? condition;
}

class Advisory {
  const Advisory({
    required this.id,
    required this.type,
    required this.title,
    required this.summary,
    this.severity,
    this.source,
  });
  factory Advisory.fromJson(Json json) => Advisory(
    id: (json['id'] as num).toInt(),
    type: json['type']?.toString() ?? '',
    title: json['title']?.toString() ?? '',
    summary: json['summary']?.toString() ?? '',
    severity: json['severity']?.toString(),
    source: json['source']?.toString(),
  );
  final int id;
  final String type;
  final String title;
  final String summary;
  final String? severity;
  final String? source;
}

class FinanceProduct {
  const FinanceProduct({
    required this.id,
    required this.name,
    required this.category,
    required this.currency,
    required this.partnerName,
    this.eligibility,
    this.minimumAmount,
    this.maximumAmount,
    this.maximumTenorMonths,
  });
  factory FinanceProduct.fromJson(Json json) => FinanceProduct(
    id: (json['id'] as num).toInt(),
    name: json['name']?.toString() ?? '',
    category: json['category']?.toString() ?? '',
    currency: json['currency']?.toString() ?? 'NGN',
    partnerName: (json['partner'] as Json?)?['name']?.toString() ?? 'Partner',
    eligibility: json['eligibility_summary']?.toString(),
    minimumAmount: (json['minimum_amount'] as num?)?.toDouble(),
    maximumAmount: (json['maximum_amount'] as num?)?.toDouble(),
    maximumTenorMonths: (json['maximum_tenor_months'] as num?)?.toInt(),
  );
  final int id;
  final String name;
  final String category;
  final String currency;
  final String partnerName;
  final String? eligibility;
  final double? minimumAmount;
  final double? maximumAmount;
  final int? maximumTenorMonths;
}

class Diagnosis {
  const Diagnosis({
    required this.id,
    required this.status,
    required this.responseLanguage,
    this.diagnosis,
    this.recommendation,
    this.createdAt,
  });
  factory Diagnosis.fromJson(Json json) => Diagnosis(
    id: json['id'].toString(),
    status: json['status']?.toString() ?? 'submitted',
    responseLanguage: json['response_language']?.toString() ?? 'en',
    diagnosis: json['diagnosis']?.toString(),
    recommendation: json['recommendation']?.toString(),
    createdAt: json['created_at']?.toString(),
  );
  final String id;
  final String status;
  final String responseLanguage;
  final String? diagnosis;
  final String? recommendation;
  final String? createdAt;
}

class VoiceRequest {
  const VoiceRequest({
    required this.id,
    required this.status,
    required this.sourceLanguage,
    required this.responseLanguage,
    this.transcript,
    this.translatedTranscript,
    this.guidance,
    this.safetyNote,
    this.createdAt,
  });
  factory VoiceRequest.fromJson(Json json) => VoiceRequest(
    id: json['id'].toString(),
    status: json['status']?.toString() ?? 'submitted',
    sourceLanguage: json['source_language']?.toString() ?? 'ha',
    responseLanguage: json['response_language']?.toString() ?? 'ha',
    transcript: json['transcript']?.toString(),
    translatedTranscript: json['translated_transcript']?.toString(),
    guidance: json['guidance']?.toString(),
    safetyNote: json['safety_note']?.toString(),
    createdAt: json['created_at']?.toString(),
  );
  final String id;
  final String status;
  final String sourceLanguage;
  final String responseLanguage;
  final String? transcript;
  final String? translatedTranscript;
  final String? guidance;
  final String? safetyNote;
  final String? createdAt;
}

class FinanceApplication {
  const FinanceApplication({
    required this.id,
    required this.status,
    required this.productName,
    required this.farmName,
    required this.purpose,
  });
  factory FinanceApplication.fromJson(Json json) => FinanceApplication(
    id: json['id'].toString(),
    status: json['status']?.toString() ?? 'submitted',
    productName:
        (json['product'] as Json?)?['name']?.toString() ??
        'Equipment application',
    farmName: (json['farm'] as Json?)?['name']?.toString() ?? 'Farm',
    purpose: json['purpose']?.toString() ?? '',
  );
  final String id;
  final String status;
  final String productName;
  final String farmName;
  final String purpose;
}
