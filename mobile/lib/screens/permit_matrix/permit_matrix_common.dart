Map<String, dynamic> permitMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

List<dynamic> permitList(dynamic value) => value is List ? value : const [];

int? permitInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '');
}

String permitText(dynamic value) => value?.toString().trim() ?? '';

String permitDate(dynamic value) {
  final text = permitText(value);
  return text.isEmpty ? '' : text.split('T').first.split(' ').first;
}

String permitRelationName(dynamic relation, [String fallback = '']) {
  final data = permitMap(relation);
  final name = data['name'] ?? data['code'];
  return name == null ? fallback : permitText(name);
}

String permitInspectorName(Map<String, dynamic> inspection) {
  final current = permitRelationName(inspection['inspector']);
  if (current.isNotEmpty) return current;
  return permitRelationName(inspection['legacy_inspector'], 'Inspector');
}

bool permitHasFinding(Map<String, dynamic> inspection) {
  if (permitText(inspection['finding_status']) == 'Ada Temuan') return true;
  return permitText(inspection['permit_findings']).isNotEmpty;
}
