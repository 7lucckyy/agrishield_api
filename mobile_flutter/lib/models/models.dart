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
    required this.acquisitionOptions,
    required this.reasons,
    this.description,
    this.capacity,
    this.matchScore,
  });
  factory FinanceProduct.fromJson(Json json) => FinanceProduct(
    id: (json['id'] as num).toInt(),
    name: json['name']?.toString() ?? '',
    category: json['category']?.toString() ?? '',
    acquisitionOptions: (json['acquisition_options'] as List? ?? const [])
        .map((value) => value.toString())
        .toList(),
    reasons: (json['why_recommended'] as List? ?? const [])
        .map((value) => value.toString())
        .toList(),
    description: json['description']?.toString(),
    capacity: json['capacity']?.toString(),
    matchScore: (json['match_score'] as num?)?.toInt(),
  );
  final int id;
  final String name;
  final String category;
  final List<String> acquisitionOptions;
  final List<String> reasons;
  final String? description;
  final String? capacity;
  final int? matchScore;
}

class AgronomyAdvice {
  const AgronomyAdvice({
    required this.id,
    required this.language,
    required this.question,
    required this.answer,
    required this.nextSteps,
    required this.uncertainty,
    required this.safetyNote,
  });
  factory AgronomyAdvice.fromJson(Json json) => AgronomyAdvice(
    id: json['id']?.toString() ?? '',
    language: json['language']?.toString() ?? 'en',
    question: json['question']?.toString() ?? '',
    answer: json['answer']?.toString() ?? '',
    nextSteps: (json['next_steps'] as List? ?? const [])
        .map((value) => value.toString())
        .toList(),
    uncertainty: json['uncertainty']?.toString() ?? '',
    safetyNote: json['safety_note']?.toString() ?? '',
  );
  final String id;
  final String language;
  final String question;
  final String answer;
  final List<String> nextSteps;
  final String uncertainty;
  final String safetyNote;
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
    this.decisionNote,
  });
  factory FinanceApplication.fromJson(Json json) => FinanceApplication(
    id: json['id'].toString(),
    status: json['status']?.toString() ?? 'submitted',
    productName:
        (json['product'] as Json?)?['name']?.toString() ??
        'Equipment application',
    farmName: (json['farm'] as Json?)?['name']?.toString() ?? 'Farm',
    purpose: json['purpose']?.toString() ?? '',
    decisionNote: json['decision_note']?.toString(),
  );
  final String id;
  final String status;
  final String productName;
  final String farmName;
  final String purpose;
  final String? decisionNote;
}

class AgriculturalOffer {
  const AgriculturalOffer({
    required this.id,
    required this.category,
    required this.title,
    required this.partnerName,
    required this.partnerVerified,
    this.description,
    this.unitPrice,
    this.currency = 'NGN',
    this.unit,
    this.minimumQuantity,
    this.remainingQuantity,
    this.state,
    this.requirements = const [],
    this.endsAt,
  });

  factory AgriculturalOffer.fromJson(Json json) {
    final pricing = json['pricing'] as Json? ?? const {};
    final partner = json['partner'] as Json? ?? const {};
    final location = json['location'] as Json? ?? const {};
    return AgriculturalOffer(
      id: (json['id'] as num).toInt(),
      category: json['category']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      partnerName: partner['name']?.toString() ?? 'Verified partner',
      partnerVerified: partner['verified'] == true,
      description: json['description']?.toString(),
      unitPrice: (pricing['unit_price'] as num?)?.toDouble(),
      currency: pricing['currency']?.toString() ?? 'NGN',
      unit: pricing['unit']?.toString(),
      minimumQuantity: (pricing['minimum_quantity'] as num?)?.toDouble(),
      remainingQuantity: (pricing['remaining_quantity'] as num?)?.toDouble(),
      state: location['state']?.toString(),
      requirements: (json['requirements'] as List? ?? const [])
          .map((value) => value.toString())
          .toList(),
      endsAt: json['ends_at']?.toString(),
    );
  }

  final int id;
  final String category;
  final String title;
  final String partnerName;
  final bool partnerVerified;
  final String? description;
  final double? unitPrice;
  final String currency;
  final String? unit;
  final double? minimumQuantity;
  final double? remainingQuantity;
  final String? state;
  final List<String> requirements;
  final String? endsAt;
}

class AgriculturalServiceRequest {
  const AgriculturalServiceRequest({
    required this.id,
    required this.requestType,
    required this.status,
    required this.offerTitle,
    required this.partnerName,
    required this.farmName,
    this.quantity,
    this.unit,
    this.decisionNote,
  });
  factory AgriculturalServiceRequest.fromJson(Json json) {
    final offer = json['offer'] as Json? ?? const {};
    final farm = json['farm'] as Json? ?? const {};
    return AgriculturalServiceRequest(
      id: json['id']?.toString() ?? '',
      requestType: json['request_type']?.toString() ?? '',
      status: json['status']?.toString() ?? 'submitted',
      offerTitle: offer['title']?.toString() ?? 'Agricultural service',
      partnerName: offer['partner']?.toString() ?? 'Partner',
      farmName: farm['name']?.toString() ?? 'Farm',
      quantity: (json['quantity'] as num?)?.toDouble(),
      unit: json['unit']?.toString(),
      decisionNote: json['decision_note']?.toString(),
    );
  }
  final String id;
  final String requestType;
  final String status;
  final String offerTitle;
  final String partnerName;
  final String farmName;
  final double? quantity;
  final String? unit;
  final String? decisionNote;
}

class FarmLedgerEntry {
  const FarmLedgerEntry({
    required this.id,
    required this.entryType,
    required this.category,
    required this.occurredOn,
    required this.description,
    required this.amount,
    required this.source,
    this.quantity,
    this.unit,
    this.voiceTranscript,
  });
  factory FarmLedgerEntry.fromJson(Json json) => FarmLedgerEntry(
    id: json['id']?.toString() ?? '',
    entryType: json['entry_type']?.toString() ?? 'expense',
    category: json['category']?.toString() ?? '',
    occurredOn: json['occurred_on']?.toString() ?? '',
    description: json['description']?.toString() ?? '',
    amount: (json['amount'] as num?)?.toDouble() ?? 0,
    source: json['source']?.toString() ?? 'manual',
    quantity: (json['quantity'] as num?)?.toDouble(),
    unit: json['unit']?.toString(),
    voiceTranscript: json['voice_transcript']?.toString(),
  );
  final String id;
  final String entryType;
  final String category;
  final String occurredOn;
  final String description;
  final double amount;
  final String source;
  final double? quantity;
  final String? unit;
  final String? voiceTranscript;
}

class FarmProfitability {
  const FarmProfitability({
    required this.income,
    required this.expenses,
    required this.grossProfit,
    required this.harvestQuantity,
    required this.entryCount,
    required this.isComplete,
    required this.notice,
    this.grossMarginPercent,
  });
  factory FarmProfitability.fromJson(Json json) => FarmProfitability(
    income: (json['income'] as num?)?.toDouble() ?? 0,
    expenses: (json['expenses'] as num?)?.toDouble() ?? 0,
    grossProfit: (json['gross_profit'] as num?)?.toDouble() ?? 0,
    grossMarginPercent: (json['gross_margin_percent'] as num?)?.toDouble(),
    harvestQuantity: (json['harvest_quantity'] as num?)?.toDouble() ?? 0,
    entryCount: (json['entry_count'] as num?)?.toInt() ?? 0,
    isComplete: json['is_complete'] == true,
    notice: json['notice']?.toString() ?? '',
  );
  final double income;
  final double expenses;
  final double grossProfit;
  final double? grossMarginPercent;
  final double harvestQuantity;
  final int entryCount;
  final bool isComplete;
  final String notice;
}

class AlertPreference {
  const AlertPreference({
    required this.inAppEnabled,
    required this.pushEnabled,
    required this.smsEnabled,
    required this.whatsappEnabled,
    required this.voiceCallEnabled,
    required this.language,
    required this.minimumSeverity,
    required this.consent,
    this.phone,
    this.whatsappPhone,
  });
  factory AlertPreference.fromJson(Json json) => AlertPreference(
    inAppEnabled: json['in_app_enabled'] == true,
    pushEnabled: json['push_enabled'] == true,
    smsEnabled: json['sms_enabled'] == true,
    whatsappEnabled: json['whatsapp_enabled'] == true,
    voiceCallEnabled: json['voice_call_enabled'] == true,
    language: json['language']?.toString() ?? 'en',
    minimumSeverity: json['minimum_severity']?.toString() ?? 'medium',
    consent: json['consent'] == true,
    phone: json['phone']?.toString(),
    whatsappPhone: json['whatsapp_phone']?.toString(),
  );
  final bool inAppEnabled;
  final bool pushEnabled;
  final bool smsEnabled;
  final bool whatsappEnabled;
  final bool voiceCallEnabled;
  final String language;
  final String minimumSeverity;
  final bool consent;
  final String? phone;
  final String? whatsappPhone;
}
