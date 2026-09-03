import 'package:flutter/material.dart';

/// Observer global untuk mendeteksi navigasi kembali (misal setelah membuat
/// inspeksi) agar layar yang memakai [RouteAware] dapat memuat ulang.
final RouteObserver<ModalRoute<void>> routeObserver =
    RouteObserver<ModalRoute<void>>();