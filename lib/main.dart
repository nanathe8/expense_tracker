import 'package:flutter/material.dart';
import 'StatisticsScreen.dart';
import 'addBudgetScreen.dart';
import 'budgetScreen.dart';
import 'groupDashboardScreen.dart';
import 'groupExpenseScreen.dart';
import 'income.dart';
import 'login_screen.dart'; // Import the login screen
import 'home_screen.dart'; // Import the home screen
import 'addExpenseScreen.dart'; // Import the add expense screen
import 'AddTransactionScreen.dart';
import 'navigator_observer.dart';
import 'package:hypespend_tracker/groupDashboardScreen.dart';


void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      // Define the initial screen to show (LoginScreen)
      initialRoute: '/login_screen',
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
        '/login_screen': (context) => const LoginScreen(),
        '/home_screen': (context) => const HomeScreen(),
        '/addExpenseScreen': (context) => const AddExpenseScreen(),
        '/AddTransactionScreen': (context) => const AddTransactionScreen(),
        '/addBudgetScreen': (context) => const BudgetScreen(),
        '/income': (context) => const IncomeScreen(),
        // '/profile': (context) => const ProfileScreen(),
        '/StatisticsScreen': (context) => const StatisticsScreen(),
        '/budgetScreen': (context) => const BudgetListPage(),
        '/home_screen': (context) => HomeScreen(),
        '/groupExpenseScreen': (context) => GroupExpenseScreen(),  // Create this screen
        '/login_screen': (context) => LoginScreen(),
        '/groupDashboardScreen': (context) => const GroupDashboardScreen(groupID: '',),

      },
      theme: ThemeData(
        primarySwatch: Colors.deepPurple,
      ),
    );
  }
}
