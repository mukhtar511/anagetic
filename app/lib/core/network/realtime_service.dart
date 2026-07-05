import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

/// Live updates over the Pusher protocol (Soketi in dev). SPEC §1/§3.
///
/// Backs three realtime surfaces:
///  - chat messages          (channel: private-conversation.{id})
///  - smart-request offers    (channel: private-smart-request.{id})
///  - notifications           (channel: private-user.{id})
///
/// The Laravel side broadcasts `ShouldBroadcast` events on these channels;
/// this client subscribes and forwards them as typed callbacks. The Soketi
/// endpoint is provided via [cluster] (host) and the Sanctum-authorized
/// private-channel auth flows through [onAuthorizer].
class RealtimeService {
  RealtimeService();

  final _pusher = PusherChannelsFlutter.getInstance();
  bool _initialized = false;

  Future<void> init({
    required String key,
    required String cluster,
    required String Function() bearerToken,
    bool useTls = false,
  }) async {
    if (_initialized) return;
    await _pusher.init(
      apiKey: key,
      cluster: cluster,
      useTLS: useTls,
      onAuthorizer: (channelName, socketId, options) async {
        // Laravel Sanctum-authorized private channel auth.
        return {
          'headers': {'Authorization': 'Bearer ${bearerToken()}'},
        };
      },
    );
    await _pusher.connect();
    _initialized = true;
  }

  Future<void> subscribe(
    String channel, {
    required void Function(Map<String, dynamic> data) onEvent,
  }) async {
    await _pusher.subscribe(
      channelName: channel,
      onEvent: (dynamic event) {
        if (event is PusherEvent) {
          onEvent({'event': event.eventName, 'data': event.data});
        }
      },
    );
  }

  Future<void> unsubscribe(String channel) =>
      _pusher.unsubscribe(channelName: channel);

  Future<void> disconnect() async {
    if (_initialized) {
      await _pusher.disconnect();
      _initialized = false;
    }
  }
}

final realtimeServiceProvider = Provider<RealtimeService>((ref) {
  final service = RealtimeService();
  ref.onDispose(service.disconnect);
  return service;
});
