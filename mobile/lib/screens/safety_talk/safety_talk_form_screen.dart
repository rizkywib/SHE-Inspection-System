import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../services/api_service.dart';
import 'safety_talk_common.dart';

class SafetyTalkFormScreen extends StatefulWidget {
  const SafetyTalkFormScreen({
    super.key,
    this.training,
    this.photoPicker,
  });

  final Map<String, dynamic>? training;
  final Future<File?> Function()? photoPicker;

  @override
  State<SafetyTalkFormScreen> createState() => _SafetyTalkFormScreenState();
}

class _SafetyTalkFormScreenState extends State<SafetyTalkFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _imagePicker = ImagePicker();
  late final TextEditingController _dateController;
  late final TextEditingController _topicController;
  late final TextEditingController _ecogreenController;
  late final TextEditingController _outsourcingController;
  late final TextEditingController _contractorController;
  late final TextEditingController _durationController;

  List<dynamic> _speakers = const [];
  List<dynamic> _areas = const [];
  int? _speakerId;
  int? _area;
  File? _activityPhoto;
  String? _existingPhotoUrl;
  String? _photoError;
  bool _isLoading = true;
  bool _isSaving = false;
  String? _error;

  bool get _isEdit => widget.training != null;

  @override
  void initState() {
    super.initState();
    final training = widget.training ?? const <String, dynamic>{};
    _dateController = TextEditingController(
      text: safetyTalkDate(
        training['implementation_date'] ??
            DateTime.now().toIso8601String().split('T').first,
      ),
    );
    _topicController = TextEditingController(
      text: safetyTalkText(training['topic'], fallback: ''),
    );
    _ecogreenController = TextEditingController(
      text: (safetyTalkInt(training['ecogreen_participants']) ?? 0).toString(),
    );
    _outsourcingController = TextEditingController(
      text:
          (safetyTalkInt(training['outsourcing_participants']) ?? 0).toString(),
    );
    _contractorController = TextEditingController(
      text:
          (safetyTalkInt(training['contractor_participants']) ?? 0).toString(),
    );
    _durationController = TextEditingController(
      text: safetyTalkText(training['duration_minutes'], fallback: ''),
    );
    _speakerId = safetyTalkInt(training['speaker_id']);
    _area = safetyTalkInt(training['implementation_area']);
    _existingPhotoUrl = safetyTalkText(
      training['activity_photo_url'],
      fallback: '',
    );
    for (final controller in [
      _ecogreenController,
      _outsourcingController,
      _contractorController,
    ]) {
      controller.addListener(_updateTotal);
    }
    _loadMasterData();
  }

  @override
  void dispose() {
    _dateController.dispose();
    _topicController.dispose();
    _ecogreenController.dispose();
    _outsourcingController.dispose();
    _contractorController.dispose();
    _durationController.dispose();
    super.dispose();
  }

  void _updateTotal() {
    if (mounted) setState(() {});
  }

  int get _totalParticipants =>
      (int.tryParse(_ecogreenController.text) ?? 0) +
      (int.tryParse(_outsourcingController.text) ?? 0) +
      (int.tryParse(_contractorController.text) ?? 0);

  Future<void> _loadMasterData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final master = await context.read<ApiService>().getSafetyTalkMasterData();
      if (!mounted) return;
      final speakers = safetyTalkList(master['speakers']);
      final areas = safetyTalkList(master['areas']);
      setState(() {
        _speakers = speakers;
        _areas = areas;
        if (!_containsSpeaker(_speakerId, speakers)) _speakerId = null;
        if (!areas.map(safetyTalkInt).contains(_area)) _area = null;
        _isLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = error.toString();
      });
    }
  }

  bool _containsSpeaker(int? id, List<dynamic> rows) {
    if (id == null) return false;
    return rows.map(safetyTalkMap).any((row) => safetyTalkInt(row['id']) == id);
  }

  String? _requiredText(String? value) {
    return value == null || value.trim().isEmpty
        ? 'Field ini wajib diisi.'
        : null;
  }

  String? _requiredChoice(int? value) {
    return value == null ? 'Field ini wajib dipilih.' : null;
  }

  String? _nonNegativeNumber(String? value) {
    final number = int.tryParse(value ?? '');
    if (number == null) return 'Masukkan angka bulat.';
    if (number < 0) return 'Nilai minimal 0.';
    return null;
  }

  String? _positiveNumber(String? value) {
    final number = int.tryParse(value ?? '');
    if (number == null) return 'Masukkan angka bulat.';
    if (number < 1) return 'Durasi minimal 1 menit.';
    return null;
  }

  Future<void> _pickDate() async {
    final initial = DateTime.tryParse(_dateController.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      _dateController.text = selected.toIso8601String().split('T').first;
    }
  }

  Future<File?> _pickFromDevice() async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: const Text('Ambil Foto'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Pilih dari Galeri'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return null;
    final selected = await _imagePicker.pickImage(
      source: source,
      imageQuality: 90,
      maxWidth: 1920,
    );
    return selected == null ? null : File(selected.path);
  }

  Future<void> _pickPhoto() async {
    try {
      final selected = widget.photoPicker != null
          ? await widget.photoPicker!()
          : await _pickFromDevice();
      if (selected == null || !mounted) return;
      final extension = selected.path.split('.').last.toLowerCase();
      if (!const {'jpg', 'jpeg', 'png'}.contains(extension)) {
        setState(() => _photoError = 'Gunakan foto JPG, JPEG, atau PNG.');
        return;
      }
      if (widget.photoPicker == null &&
          await selected.length() > 5 * 1024 * 1024) {
        setState(() => _photoError = 'Ukuran foto maksimal 5 MB.');
        return;
      }
      setState(() {
        _activityPhoto = selected;
        _photoError = null;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _photoError = 'Foto gagal dipilih: $error');
    }
  }

  Future<void> _save() async {
    final valid = _formKey.currentState!.validate();
    if (!_isEdit && _activityPhoto == null) {
      setState(() => _photoError = 'Foto Penyampaian wajib dipilih.');
    }
    if (!valid || (!_isEdit && _activityPhoto == null)) return;

    setState(() => _isSaving = true);
    final fields = <String, String>{
      'speaker_id': _speakerId.toString(),
      'implementation_date': _dateController.text,
      'topic': _topicController.text.trim(),
      'ecogreen_participants': _ecogreenController.text,
      'outsourcing_participants': _outsourcingController.text,
      'contractor_participants': _contractorController.text,
      'duration_minutes': _durationController.text,
      'implementation_area': _area.toString(),
    };

    try {
      final api = context.read<ApiService>();
      final response = _isEdit
          ? await api.updateSafetyTalkTraining(
              safetyTalkInt(widget.training!['id'])!,
              fields,
              activityPhoto: _activityPhoto,
            )
          : await api.createSafetyTalkTraining(fields, _activityPhoto!);
      if (!mounted) return;
      if (response.containsKey('error')) {
        setState(() => _isSaving = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response['error'].toString()),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }
      Navigator.pop(context, 'saved');
    } catch (error) {
      if (!mounted) return;
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Data gagal disimpan: $error'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _delete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Safety Talk/Training?'),
        content: const Text(
          'Data dan foto kegiatan akan dihapus. Tindakan ini tidak dapat dibatalkan.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    final id = safetyTalkInt(widget.training?['id']);
    if (id == null) return;
    final response =
        await context.read<ApiService>().deleteSafetyTalkTraining(id);
    if (!mounted) return;
    if (response.containsKey('error')) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response['error'].toString()),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    Navigator.pop(context, 'deleted');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          _isEdit ? 'Edit Safety Talk/Training' : 'Tambah Safety Talk/Training',
        ),
      ),
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? _FormError(message: _error!, onRetry: _loadMasterData)
                : Form(
                    key: _formKey,
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Field bertanda * wajib diisi.',
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                          const SizedBox(height: 16),
                          DropdownButtonFormField<int>(
                            key: const ValueKey('safety_speaker_id'),
                            initialValue: _speakerId,
                            isExpanded: true,
                            decoration: const InputDecoration(
                              labelText: 'Pembicara Materi *',
                              prefixIcon: Icon(Icons.person_outline),
                              border: OutlineInputBorder(),
                            ),
                            hint: const Text('Pilih Pembicara'),
                            items: _speakers.map(safetyTalkMap).map((row) {
                              return DropdownMenuItem<int>(
                                value: safetyTalkInt(row['id']),
                                child: Text(
                                  safetyTalkText(row['name']),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              );
                            }).toList(),
                            onChanged: (value) =>
                                setState(() => _speakerId = value),
                            validator: _requiredChoice,
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
                            key: const ValueKey('safety_date'),
                            controller: _dateController,
                            readOnly: true,
                            decoration: const InputDecoration(
                              labelText: 'Tanggal Pelaksanaan *',
                              prefixIcon: Icon(Icons.calendar_today_outlined),
                              border: OutlineInputBorder(),
                            ),
                            validator: _requiredText,
                            onTap: _pickDate,
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
                            key: const ValueKey('safety_topic'),
                            controller: _topicController,
                            minLines: 4,
                            maxLines: 6,
                            maxLength: 1000,
                            decoration: const InputDecoration(
                              labelText:
                                  'Topik / Materi Safety Talk / Training On Site *',
                              prefixIcon: Icon(Icons.subject_outlined),
                              border: OutlineInputBorder(),
                              alignLabelWithHint: true,
                            ),
                            validator: _requiredText,
                          ),
                          const SizedBox(height: 4),
                          _NumberField(
                            fieldKey: 'safety_ecogreen',
                            controller: _ecogreenController,
                            label: 'Jumlah Peserta Ecogreen *',
                            validator: _nonNegativeNumber,
                          ),
                          const SizedBox(height: 12),
                          _NumberField(
                            fieldKey: 'safety_outsourcing',
                            controller: _outsourcingController,
                            label: 'Jumlah Peserta Outsourcing *',
                            validator: _nonNegativeNumber,
                          ),
                          const SizedBox(height: 12),
                          _NumberField(
                            fieldKey: 'safety_contractor',
                            controller: _contractorController,
                            label: 'Jumlah Peserta Contractor *',
                            validator: _nonNegativeNumber,
                          ),
                          const SizedBox(height: 12),
                          InputDecorator(
                            key: const ValueKey('safety_total'),
                            decoration: const InputDecoration(
                              labelText: 'Total Peserta',
                              prefixIcon: Icon(Icons.groups_outlined),
                              border: OutlineInputBorder(),
                            ),
                            child: Text(
                              '$_totalParticipants',
                              style:
                                  const TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ),
                          const SizedBox(height: 12),
                          _NumberField(
                            fieldKey: 'safety_duration',
                            controller: _durationController,
                            label: 'Durasi Penyampaian (menit) *',
                            validator: _positiveNumber,
                          ),
                          const SizedBox(height: 12),
                          DropdownButtonFormField<int>(
                            key: const ValueKey('safety_area'),
                            initialValue: _area,
                            decoration: const InputDecoration(
                              labelText: 'Area Pelaksanaan *',
                              prefixIcon: Icon(Icons.place_outlined),
                              border: OutlineInputBorder(),
                            ),
                            hint: const Text('Pilih Area'),
                            items:
                                _areas.map(safetyTalkInt).whereType<int>().map(
                              (area) {
                                return DropdownMenuItem<int>(
                                  value: area,
                                  child: Text('Area $area'),
                                );
                              },
                            ).toList(),
                            onChanged: (value) => setState(() => _area = value),
                            validator: _requiredChoice,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            'Foto Penyampaian${_isEdit ? '' : ' *'}',
                            style: Theme.of(context).textTheme.titleSmall,
                          ),
                          const SizedBox(height: 8),
                          _PhotoPreview(
                            file: _activityPhoto,
                            networkUrl: _existingPhotoUrl,
                          ),
                          const SizedBox(height: 8),
                          OutlinedButton.icon(
                            key: const ValueKey('safety_photo'),
                            onPressed: _isSaving ? null : _pickPhoto,
                            icon: const Icon(Icons.add_a_photo_outlined),
                            label: Text(
                              _activityPhoto == null
                                  ? 'Pilih Foto'
                                  : 'Ganti Foto',
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'JPG, JPEG, atau PNG. Maksimal 5 MB.${_isEdit ? ' Kosongkan jika foto tidak diganti.' : ''}',
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                          if (_photoError != null) ...[
                            const SizedBox(height: 4),
                            Text(
                              _photoError!,
                              style: TextStyle(
                                color: Theme.of(context).colorScheme.error,
                              ),
                            ),
                          ],
                          const SizedBox(height: 24),
                          Row(
                            children: [
                              if (_isEdit) ...[
                                Expanded(
                                  child: OutlinedButton.icon(
                                    key: const ValueKey('safety_delete'),
                                    onPressed: _isSaving ? null : _delete,
                                    icon: const Icon(Icons.delete_outline),
                                    label: const Text('Hapus'),
                                  ),
                                ),
                                const SizedBox(width: 12),
                              ],
                              Expanded(
                                child: FilledButton.icon(
                                  key: const ValueKey('safety_save'),
                                  onPressed: _isSaving ? null : _save,
                                  icon: _isSaving
                                      ? const SizedBox(
                                          width: 18,
                                          height: 18,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                            color: Colors.white,
                                          ),
                                        )
                                      : const Icon(Icons.save_outlined),
                                  label: Text(
                                    _isSaving ? 'Menyimpan...' : 'Simpan',
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
      ),
    );
  }
}

class _NumberField extends StatelessWidget {
  const _NumberField({
    required this.fieldKey,
    required this.controller,
    required this.label,
    required this.validator,
  });

  final String fieldKey;
  final TextEditingController controller;
  final String label;
  final FormFieldValidator<String> validator;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      key: ValueKey(fieldKey),
      controller: controller,
      keyboardType: TextInputType.number,
      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.numbers_outlined),
        border: const OutlineInputBorder(),
      ),
      validator: validator,
    );
  }
}

class _PhotoPreview extends StatelessWidget {
  const _PhotoPreview({required this.file, required this.networkUrl});

  final File? file;
  final String? networkUrl;

  @override
  Widget build(BuildContext context) {
    Widget? image;
    if (file != null) {
      image = Image.file(
        file!,
        fit: BoxFit.cover,
        errorBuilder: (_, __, ___) => const Center(
          child: Icon(Icons.image_outlined, size: 48),
        ),
      );
    } else if (networkUrl != null && networkUrl!.isNotEmpty) {
      image = Image.network(
        networkUrl!,
        fit: BoxFit.cover,
        errorBuilder: (_, __, ___) => const Center(
          child: Icon(Icons.broken_image_outlined, size: 48),
        ),
      );
    }
    if (image == null) {
      return Container(
        height: 130,
        width: double.infinity,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Text('Belum ada foto dipilih'),
      );
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: SizedBox(height: 200, width: double.infinity, child: image),
    );
  }
}

class _FormError extends StatelessWidget {
  const _FormError({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.lock_outline, color: Colors.red, size: 48),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Coba Lagi'),
            ),
          ],
        ),
      ),
    );
  }
}
