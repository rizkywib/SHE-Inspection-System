Map<String, dynamic> safetyTalkMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

List<dynamic> safetyTalkList(dynamic value) {
  return value is List ? value : const <dynamic>[];
}

int? safetyTalkInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '');
}

String safetyTalkText(dynamic value, {String fallback = '-'}) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? fallback : text;
}

String safetyTalkDate(dynamic value) {
  final text = safetyTalkText(value, fallback: '');
  if (text.isEmpty) return '-';
  return text.length >= 10 ? text.substring(0, 10) : text;
}

String safetyTalkDateTime(dynamic value) {
  final text = safetyTalkText(value, fallback: '');
  if (text.isEmpty) return '-';
  final parsed = DateTime.tryParse(text)?.toLocal();
  if (parsed == null) return text;
  String two(int number) => number.toString().padLeft(2, '0');
  return '${two(parsed.day)}-${two(parsed.month)}-${parsed.year} '
      '${two(parsed.hour)}:${two(parsed.minute)}';
}

String safetyTalkSpeakerName(Map<String, dynamic> row) {
  final speaker = safetyTalkMap(row['speaker']);
  final legacySpeaker = safetyTalkMap(row['legacy_speaker']);
  return safetyTalkText(
    speaker['name'] ?? legacySpeaker['name'],
  );
}

int safetyTalkTotalParticipants(Map<String, dynamic> row) {
  return safetyTalkInt(row['total_participants']) ??
      (safetyTalkInt(row['ecogreen_participants']) ?? 0) +
          (safetyTalkInt(row['outsourcing_participants']) ?? 0) +
          (safetyTalkInt(row['contractor_participants']) ?? 0);
}

bool safetyTalkCanModify(
  Map<String, dynamic> row,
  Map<String, dynamic> currentUser,
) {
  final role = currentUser['role']?.toString();
  if (role == 'admin' || role == 'super_admin') return true;
  final createdBy = safetyTalkInt(row['created_by']);
  final userId = safetyTalkInt(currentUser['id']);
  return createdBy != null && userId != null && createdBy == userId;
}
