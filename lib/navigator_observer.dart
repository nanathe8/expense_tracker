import 'package:flutter/cupertino.dart';

class MyNavigatorObserver extends NavigatorObserver {
  final Function onPop;

  MyNavigatorObserver({required this.onPop});

  @override
  void didPop(Route route, Route? previousRoute) {
    super.didPop(route, previousRoute);
    onPop();  // Call the callback when a route is popped
  }
}
