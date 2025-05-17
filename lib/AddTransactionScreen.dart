import 'package:flutter/material.dart';
import 'addBudgetScreen.dart';
import 'addExpenseScreen.dart';  // Import the Add Expense screen
import 'income.dart';  // Import the Add Income screen
// import 'setBudgetScreen.dart';   // Import the Set Budget screen

class AddTransactionScreen extends StatefulWidget {
  const AddTransactionScreen({Key? key}) : super(key: key);

  @override
  _AddTransactionScreenState createState() => _AddTransactionScreenState();
}

class _AddTransactionScreenState extends State<AddTransactionScreen> {
  // Default page index
  int _currentIndex = 0;

  // Method to handle page changes
  void _onTabChanged(int index) {
    setState(() {
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          // Tabs for Expense, Income, and Budget
          DefaultTabController(
            length: 3, // Number of tabs
            child: TabBar(
              labelColor: Colors.deepPurple,
              indicatorColor: Colors.deepPurple,
              tabs: const [
                Tab(text: 'Expense'),
                Tab(text: 'Income'),
                Tab(text: 'Budget'),
              ],
              onTap: (index) {
                _onTabChanged(index);  // Change the index on tap
              },
            ),
          ),
          const SizedBox(height: 10),
          // Display selected screen based on tab index
          Expanded(
            child: IndexedStack(
              index: _currentIndex, // Controls which page is shown
              children: [
                AddExpenseScreen(),  // Expense form
                IncomeScreen(),   // Income form
                BudgetScreen(),   // Budget form
              ],
            ),
          ),
        ],
      ),
    );
  }
}
