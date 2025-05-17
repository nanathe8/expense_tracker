import 'package:flutter/material.dart';
import 'addBudgetScreen.dart';
import 'income.dart';
import 'login_screen.dart'; // Import the login screen
import 'home_screen.dart'; // Import the home screen
import 'addExpenseScreen.dart'; // Import the add expense screen
import 'AddTransactionScreen.dart';
import 'navigator_observer.dart';
// import 'profile_screen.dart'; // Import your profile screen
// import 'statistics_screen.dart'; // Import the statistics screen
// import 'calendar_screen.dart'; // Import the calendar screen

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      // Define the initial screen to show (LoginScreen)
      initialRoute: '/login',
      navigatorObservers: [
        MyNavigatorObserver(
            onPop: () {
              // Trigger a data refresh when the user navigates back to HomeScreen
              print("HomeScreen is revisited, refresh data.");
              // You can directly call your data refresh function here, if needed
              // Example: myHomeScreenState.fetchData();
            }
        )
      ],
      home: const HomeScreen(),  // Set HomeScreen as the initial screen
      routes: {
        '/login': (context) => const LoginScreen(),
        '/home_screen': (context) => const HomeScreen(),
        '/addExpenseScreen': (context) => const AddExpenseScreen(),
        '/AddTransactionScreen': (context) => const AddTransactionScreen(),
        '/addBudgetScreen': (context) => const BudgetScreen(),
        '/income': (context) => const IncomeScreen()
        // '/profile': (context) => const ProfileScreen(),
        // '/statistics': (context) => const StatisticsScreen(),
        // '/calendar': (context) => const CalendarScreen(),
      },
      theme: ThemeData(
        primarySwatch: Colors.deepPurple,
      ),
    );
  }
}
