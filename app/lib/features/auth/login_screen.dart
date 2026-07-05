import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../data/api_repository.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';

/// Two-step OTP login. SPEC §3.15.
class LoginScreen extends ConsumerStatefulWidget {
  final String? from;
  const LoginScreen({super.key, this.from});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  int _step = 1; // 1 = phone, 2 = code
  final _phoneCtrl = TextEditingController();
  int? _regionId;
  List<Region> _regions = const [];
  bool _loadingRegions = true;

  final List<TextEditingController> _codeCtrls =
      List.generate(4, (_) => TextEditingController());
  final List<FocusNode> _codeNodes = List.generate(4, (_) => FocusNode());

  String? _phoneError;
  String? _codeError;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _loadRegions();
  }

  @override
  void dispose() {
    _phoneCtrl.dispose();
    for (final c in _codeCtrls) {
      c.dispose();
    }
    for (final n in _codeNodes) {
      n.dispose();
    }
    super.dispose();
  }

  Future<void> _loadRegions() async {
    try {
      final regions = await ref.read(apiRepositoryProvider).regions();
      if (!mounted) return;
      setState(() {
        _regions = regions;
        _regionId ??= regions.isNotEmpty ? regions.first.id : null;
        _loadingRegions = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingRegions = false);
    }
  }

  String get _phone => '+966${_phoneCtrl.text.trim()}';

  bool _validPhone() => RegExp(r'^5\d{8}$').hasMatch(_phoneCtrl.text.trim());

  String _errorMessage(Object e) {
    if (e is DioException && e.error is ApiException) {
      return (e.error as ApiException).message;
    }
    if (e is ApiException) return e.message;
    if (e is DioException) return e.message ?? 'حدث خطأ، حاولي مرة ثانية';
    return 'حدث خطأ، حاولي مرة ثانية';
  }

  Future<void> _requestOtp() async {
    if (!_validPhone()) {
      setState(() => _phoneError = 'أدخلي رقم جوال صحيح (٩ أرقام تبدأ بـ 5)');
      return;
    }
    setState(() {
      _phoneError = null;
      _sending = true;
    });
    try {
      await ref.read(authProvider.notifier).requestOtp(_phone);
      if (!mounted) return;
      setState(() {
        _step = 2;
        _sending = false;
      });
      _codeNodes.first.requestFocus();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _sending = false;
        _phoneError = _errorMessage(e);
      });
    }
  }

  String get _code => _codeCtrls.map((c) => c.text).join();

  Future<void> _verify() async {
    if (_code.length < 4) {
      setState(() => _codeError = 'الكود غير صحيح — تأكدي من الرسالة وحاولي مرة ثانية');
      return;
    }
    setState(() {
      _codeError = null;
      _sending = true;
    });
    try {
      await ref.read(authProvider.notifier).verifyOtp(_phone, _code, _regionId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('✓ تم التحقق — أهلًا فيك في أناقتك!')),
      );
      context.go(widget.from ?? '/');
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _sending = false;
        _codeError = _errorMessage(e);
        for (final c in _codeCtrls) {
          c.clear();
        }
      });
      _codeNodes.first.requestFocus();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('أناقتك'),
        leading: context.canPop()
            ? IconButton(
                icon: const Icon(Icons.arrow_forward),
                onPressed: () => context.pop(),
              )
            : null,
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: _step == 1 ? _phoneStep() : _codeStep(),
            ),
          ),
        ),
      ),
    );
  }

  Widget _phoneStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const _Logo(),
        const SizedBox(height: 24),
        Text('سجّلي دخولك',
            style: Theme.of(context).textTheme.headlineSmall,
            textAlign: TextAlign.center),
        const SizedBox(height: 24),
        const Text('رقم الجوال',
            style: TextStyle(fontWeight: FontWeight.w600, color: AppColors.ink)),
        const SizedBox(height: 8),
        Row(
          textDirection: TextDirection.ltr,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
              decoration: BoxDecoration(
                color: AppColors.roseSoft,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Text('+966',
                  style: TextStyle(fontWeight: FontWeight.w700)),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: TextField(
                controller: _phoneCtrl,
                keyboardType: TextInputType.phone,
                textDirection: TextDirection.ltr,
                maxLength: 9,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(9),
                ],
                decoration: const InputDecoration(
                  hintText: '5XXXXXXXX',
                  counterText: '',
                ),
              ),
            ),
          ],
        ),
        if (_phoneError != null) ...[
          const SizedBox(height: 8),
          Text(_phoneError!,
              style: const TextStyle(color: AppColors.danger, fontSize: 13)),
        ],
        const SizedBox(height: 20),
        const Text(
          'منطقتك 📍 — عشان نعرض لك القريب منك أولًا والإعلانات المميزة بمنطقتك',
          style: TextStyle(fontWeight: FontWeight.w600, color: AppColors.ink),
        ),
        const SizedBox(height: 8),
        if (_loadingRegions)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 8),
            child: LinearProgressIndicator(),
          )
        else
          DropdownButtonFormField<int>(
            initialValue: _regionId,
            isExpanded: true,
            items: [
              for (final r in _regions)
                DropdownMenuItem(value: r.id, child: Text(r.name)),
            ],
            onChanged: (v) => setState(() => _regionId = v),
          ),
        const SizedBox(height: 24),
        ElevatedButton(
          onPressed: _sending ? null : _requestOtp,
          child: _sending
              ? const SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(
                      strokeWidth: 2, color: Colors.white),
                )
              : const Text('أرسلي الكود'),
        ),
      ],
    );
  }

  Widget _codeStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const _Logo(),
        const SizedBox(height: 24),
        Text('أدخلي كود التحقق',
            style: Theme.of(context).textTheme.headlineSmall,
            textAlign: TextAlign.center),
        const SizedBox(height: 8),
        Text('أرسلنا كودًا إلى $_phone',
            textAlign: TextAlign.center,
            textDirection: TextDirection.ltr,
            style: const TextStyle(color: AppColors.mutedInk)),
        const SizedBox(height: 24),
        Row(
          textDirection: TextDirection.ltr,
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            for (int i = 0; i < 4; i++) _codeBox(i),
          ],
        ),
        if (_codeError != null) ...[
          const SizedBox(height: 12),
          Text(_codeError!,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.danger, fontSize: 13)),
        ],
        const SizedBox(height: 24),
        ElevatedButton(
          onPressed: _sending ? null : _verify,
          child: _sending
              ? const SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(
                      strokeWidth: 2, color: Colors.white),
                )
              : const Text('تحقّق'),
        ),
        const SizedBox(height: 12),
        TextButton(
          onPressed: _sending ? null : _requestOtp,
          child: const Text('إعادة إرسال الكود'),
        ),
      ],
    );
  }

  Widget _codeBox(int i) {
    return SizedBox(
      width: 60,
      child: TextField(
        controller: _codeCtrls[i],
        focusNode: _codeNodes[i],
        textAlign: TextAlign.center,
        keyboardType: TextInputType.number,
        maxLength: 1,
        style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w700),
        inputFormatters: [
          FilteringTextInputFormatter.digitsOnly,
          LengthLimitingTextInputFormatter(1),
        ],
        decoration: const InputDecoration(counterText: ''),
        onChanged: (v) {
          if (v.isNotEmpty && i < 3) {
            _codeNodes[i + 1].requestFocus();
          } else if (v.isEmpty && i > 0) {
            _codeNodes[i - 1].requestFocus();
          }
          if (i == 3 && v.isNotEmpty && _code.length == 4) {
            _verify();
          }
        },
      ),
    );
  }
}

class _Logo extends StatelessWidget {
  const _Logo();

  @override
  Widget build(BuildContext context) {
    return Text(
      'أناقتك',
      textAlign: TextAlign.center,
      style: Theme.of(context)
          .textTheme
          .displayMedium
          ?.copyWith(fontSize: 40, color: AppColors.maroon),
    );
  }
}
