import 'package:flutter/material.dart';

class PointDropdown extends StatelessWidget {
  const PointDropdown({
    super.key,
    required this.label,
    required this.points,
    required this.selectedId,
    required this.onChanged,
    this.icon = Icons.numbers,
    this.isLoading = false,
  });

  final String label;
  final List<dynamic> points;
  final int? selectedId;
  final ValueChanged<Map<String, dynamic>> onChanged;
  final IconData icon;
  final bool isLoading;

  @override
  Widget build(BuildContext context) {
    final items = points
        .map(_asMap)
        .where((point) => _asInt(point['id']) != null)
        .toList(growable: false);
    final hasSelection =
        items.any((point) => _asInt(point['id']) == selectedId);

    return DropdownButtonFormField<int>(
      initialValue: hasSelection ? selectedId : null,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon),
        border: const OutlineInputBorder(),
        suffixIcon: isLoading
            ? const Padding(
                padding: EdgeInsets.all(12),
                child: SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              )
            : null,
      ),
      hint: Text(
        isLoading
            ? 'Memuat data...'
            : items.isEmpty
                ? 'Data $label tidak tersedia'
                : '- Select $label -',
      ),
      items: items
          .map(
            (point) => DropdownMenuItem<int>(
              value: _asInt(point['id']),
              child: Text(
                _pointOption(point),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          )
          .toList(growable: false),
      onChanged: (value) {
        if (value == null) return;
        final match = items.firstWhere(
          (point) => _asInt(point['id']) == value,
          orElse: () => <String, dynamic>{},
        );
        if (match.isNotEmpty) onChanged(match);
      },
    );
  }
}

Map<String, dynamic> _asMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return <String, dynamic>{};
}

int? _asInt(dynamic value) {
  if (value is int) return value;
  return value == null ? null : int.tryParse(value.toString());
}

String _pointOption(Map<String, dynamic> point) {
  final number = (point['name_point'] ?? point['id'] ?? '').toString();
  final name = point['ket1']?.toString().trim() ?? '';
  final location = point['ket2']?.toString().trim() ?? '';
  final details = <String>[
    if (name.isNotEmpty) name,
    if (location.isNotEmpty) location,
  ].join(' - ');
  return details.isEmpty ? number : '$number - $details';
}
