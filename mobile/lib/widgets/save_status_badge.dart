import 'package:flutter/material.dart';

/// Penanda status penyimpanan sebuah inspeksi.
///
/// - [isPending] `true`: draft offline yang belum disinkronkan ke server.
/// - [isPending] `false`: data sudah tersimpan/tersinkron di server.
class SaveStatusBadge extends StatelessWidget {
  const SaveStatusBadge({super.key, required this.isPending});

  final bool isPending;

  static const Color _pendingColor = Color(0xFFB66A13);
  static const Color _syncedColor = Color(0xFF238653);

  @override
  Widget build(BuildContext context) {
    if (isPending) {
      return Tooltip(
        message: 'Menunggu sinkronisasi',
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: _pendingColor.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: _pendingColor.withValues(alpha: 0.4)),
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.cloud_upload_outlined, size: 14, color: _pendingColor),
              SizedBox(width: 4),
              Text(
                'Draft',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: _pendingColor,
                ),
              ),
            ],
          ),
        ),
      );
    }

    return const Tooltip(
      message: 'Tersimpan di server',
      child: Icon(
        Icons.cloud_done_outlined,
        size: 18,
        color: _syncedColor,
      ),
    );
  }
}