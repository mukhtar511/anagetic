// Generates the app launcher icon + splash logo from the brand identity.
// Royal-purple field with a gold arch frame and an alif (أ) stroke — echoing
// the prototype's `.arch` motif. Run: dart run tool/gen_icon.dart
import 'dart:io';

import 'package:image/image.dart';

final maroon = ColorRgb8(0x6D, 0x28, 0xA9);
final maroonDeep = ColorRgb8(0x4A, 0x18, 0x78);
final gold = ColorRgb8(0xB9, 0x8A, 0x4E);
final goldLight = ColorRgb8(0xD9, 0xB9, 0x8A);

/// Draw the arch + alif mark centered in [img] at the given scale.
void drawMark(Image img, int size, {bool background = true}) {
  final cx = size ~/ 2;

  if (background) {
    fill(img, color: maroonDeep);
    // A lighter purple centre for depth.
    fillCircle(img,
        x: cx, y: (size * 0.42).round(), radius: (size * 0.55).round(), color: maroon);
  }

  final archW = (size * 0.46).round();
  final archH = (size * 0.60).round();
  final x1 = cx - archW ~/ 2;
  final y1 = (size * 0.16).round();
  final x2 = cx + archW ~/ 2;
  final y2 = y1 + archH;
  final r = archW ~/ 2; // rounded top → arch

  // Gold arch frame (outer fill, then knock out the inside).
  fillRect(img, x1: x1, y1: y1, x2: x2, y2: y2, color: gold, radius: r);
  final t = (size * 0.035).round();
  fillRect(img,
      x1: x1 + t,
      y1: y1 + t,
      x2: x2 - t,
      y2: y2,
      color: background ? maroon : ColorRgba8(0, 0, 0, 0),
      radius: r - t);

  // Alif (أ): a tall rounded gold bar + hamza dot.
  final barW = (size * 0.06).round();
  final barX = cx - barW ~/ 2;
  fillRect(img,
      x1: barX,
      y1: (size * 0.30).round(),
      x2: barX + barW,
      y2: (size * 0.72).round(),
      color: goldLight,
      radius: barW ~/ 2);
  fillCircle(img,
      x: cx + (size * 0.055).round(),
      y: (size * 0.26).round(),
      radius: (size * 0.028).round(),
      color: goldLight);
}

void main() {
  Directory('assets').createSync(recursive: true);

  // Launcher icon — 1024², opaque brand field.
  final icon = Image(width: 1024, height: 1024, numChannels: 4);
  drawMark(icon, 1024, background: true);
  File('assets/icon.png').writeAsBytesSync(encodePng(icon));

  // Splash logo — 512², transparent (native-splash paints the purple field).
  final logo = Image(width: 512, height: 512, numChannels: 4);
  fill(logo, color: ColorRgba8(0, 0, 0, 0));
  drawMark(logo, 512, background: false);
  File('assets/splash_logo.png').writeAsBytesSync(encodePng(logo));

  stdout.writeln('Wrote assets/icon.png (1024) and assets/splash_logo.png (512)');
}
