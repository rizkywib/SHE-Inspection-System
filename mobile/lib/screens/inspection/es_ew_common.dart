import 'dart:convert';

const Map<String, String> esEwConditionLabels = {
  'water_flow_es': 'Water Flow ES',
  'water_flow_ew': 'Water Flow EW',
  'water_condition': 'Water Condition',
  'actual_valve_es': 'Actual Valve ES',
  'actual_valve_ew': 'Actual Valve EW',
  'physical_condition_es': 'Physical Condition ES',
  'physical_condition_ew': 'Physical Condition EW',
  'sign_board_condition': 'Sign Board',
  'housekeeping_condition': 'Housekeeping',
  'road_access_condition': 'Road Access',
  'sewer_condition': 'Sewer Condition',
};

Map<String, dynamic> esEwMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

List<dynamic> esEwList(dynamic value) => value is List ? value : const [];

int? esEwInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '');
}

bool esEwBool(dynamic value, {bool fallback = true}) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  final text = value?.toString().trim().toLowerCase();
  if (text == '1' || text == 'true' || text == 'yes') return true;
  if (text == '0' || text == 'false' || text == 'no') return false;
  return fallback;
}

String esEwText(dynamic value) {
  return (value ?? '')
      .toString()
      .replaceAll(RegExp(r'&#0*38;(?:amp;)?', caseSensitive: false), '&')
      .replaceAll('&amp;', '&')
      .trim();
}

bool isEsEwPoint(dynamic value) {
  final point = esEwMap(value);
  final name = esEwText(point['name_point']).toUpperCase();
  return RegExp(r'^ES\s*&\s*EW\b').hasMatch(name) ||
      name.contains('EMERGENCY SHOWER') ||
      name.contains('EYE WASH') ||
      name.contains('EYEWASH');
}

String esEwPointOption(Map<String, dynamic> point) {
  final name = esEwText(point['name_point']);
  final location = esEwText(point['ket1']);
  return location.isEmpty ? name : '$name - $location';
}

Map<String, dynamic>? findEsEwPointByQr(
  String qrValue,
  List<dynamic> values,
) {
  final points = values.map(esEwMap).where(isEsEwPoint).toList(growable: false);
  final normalized = qrValue.trim().toUpperCase();

  for (final point in points) {
    final stored = point['qr_code']?.toString().trim().toUpperCase();
    if (stored != null && stored.isNotEmpty && stored == normalized) {
      return point;
    }
  }

  int? pointId;
  try {
    final decoded = jsonDecode(qrValue);
    if (decoded is Map) {
      final data = Map<String, dynamic>.from(decoded);
      pointId = esEwInt(
        data['point_id'] ?? data['asset_id'] ?? data['id_point'] ?? data['id'],
      );
      final embeddedQr = data['qr_code']?.toString().trim().toUpperCase();
      if (embeddedQr != null && embeddedQr.isNotEmpty) {
        for (final point in points) {
          if (point['qr_code']?.toString().trim().toUpperCase() == embeddedQr) {
            return point;
          }
        }
      }
    }
  } catch (_) {
    // QR Point dari backend juga dapat berupa plain text.
  }

  final pointMatch =
      RegExp(r'^POINT-(\d+)(?:-|$)', caseSensitive: false).firstMatch(qrValue);
  pointId ??= pointMatch == null ? null : int.tryParse(pointMatch.group(1)!);

  final legacyMatch = RegExp(
    r'^(?:ES[_-]?EW|ESEW|EMERGENCY[_-]?SHOWER|EYE[_-]?WASH):(\d+)$',
    caseSensitive: false,
  ).firstMatch(qrValue);
  pointId ??= legacyMatch == null ? null : int.tryParse(legacyMatch.group(1)!);

  if (pointId == null) return null;
  for (final point in points) {
    if (esEwInt(point['id']) == pointId) return point;
  }
  return null;
}

int? matchEsEwPointId(
  Map<String, dynamic> item,
  List<dynamic> values,
) {
  final points = values.map(esEwMap).where(isEsEwPoint).toList();
  final name = esEwText(item['name']);
  final type = esEwText(item['type']);
  final detail = esEwText(item['location_detail']);

  for (final point in points) {
    if (esEwText(point['name_point']) == name &&
        esEwText(point['ket1']) == type &&
        esEwText(point['ket2']) == detail) {
      return esEwInt(point['id']);
    }
  }
  for (final point in points) {
    if (esEwText(point['name_point']) == name) return esEwInt(point['id']);
  }
  return null;
}
