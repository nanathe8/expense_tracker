import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:pie_chart/pie_chart.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'config.dart';

class StatisticsScreen extends StatefulWidget {
  const StatisticsScreen({super.key});

  @override
  _StatisticsScreenState createState() => _StatisticsScreenState();
}

class _StatisticsScreenState extends State<StatisticsScreen> {
  List<Statistic> expenses = [];
  Map<String, double> chartData = {};
  String? sessionId;

  // Month and year filters
  List<String> months = List.generate(12, (index) => DateFormat('MMMM').format(DateTime(0, index + 1)));
  List<int> years = List.generate(30, (index) => DateTime.now().year - index);

  String selectedMonthName = DateFormat('MMMM').format(DateTime.now());
  int selectedYear = DateTime.now().year;
  String selectedMonth = 'filtered'; // to trigger filter logic

  @override
  void initState() {
    super.initState();
    _loadSessionId().then((_) => fetchStatistics());
  }

  Future<void> _loadSessionId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    setState(() {
      sessionId = prefs.getString('PHPSESSID');
    });
    print('Loaded PHPSESSID: $sessionId');
  }

  Future<void> fetchStatistics() async {
    var url = Uri.parse('${CONFIG.SERVER}/getStatistics.php');

    Map<String, String> headers = {};
    if (sessionId != null) {
      headers['Cookie'] = 'PHPSESSID=$sessionId';
    }

    try {
      var uriWithParams = url;
      if (selectedMonth != 'All') {
        int monthIndex = months.indexOf(selectedMonthName) + 1;
        String formattedMonth = '$selectedYear-${monthIndex.toString().padLeft(2, '0')}';
        uriWithParams = url.replace(queryParameters: {'month': formattedMonth});
      }

      final response = await http.get(uriWithParams, headers: headers);

      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);

        if (decoded['status'] == 'success') {
          List<dynamic> data = decoded['data'];
          List<Statistic> loadedExpenses = data.map((item) => Statistic.fromJson(item)).toList();

          setState(() {
            expenses = loadedExpenses;
            chartData = _aggregateByCategory(loadedExpenses);
          });
        } else {
          _showError(decoded['message'] ?? 'Unknown error');
        }
      } else {
        _showError('Server error: ${response.statusCode}');
      }
    } catch (e) {
      _showError('Failed to load data: $e');
    }
  }

  void _showError(String message) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Error'),
        content: Text(message),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('OK')),
        ],
      ),
    );
  }

  Map<String, double> _aggregateByCategory(List<Statistic> data) {
    Map<String, double> result = {};
    for (var item in data) {
      result[item.category] = (result[item.category] ?? 0) + item.amount;
    }
    return result;
  }

  IconData _getIcon(String category) {
    switch (category.toLowerCase()) {
      case "health":
        return Icons.favorite;
      case "pet":
        return Icons.pets;
      case "shopping":
        return Icons.shopping_cart;
      case "food":
        return Icons.fastfood;
      case "utilities":
        return Icons.lightbulb;
      case "transportation":
        return Icons.directions_car;
      default:
        return Icons.label;
    }
  }

  String displaySelectedMonth() {
    return "$selectedMonthName $selectedYear";
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("STATISTICS"),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () {
            Navigator.pushReplacementNamed(context, '/home_screen');
          },
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            // Filter row using dropdowns
            Row(
              children: [
                Expanded(
                  child: DropdownButton<String>(
                    isExpanded: true,
                    value: selectedMonthName,
                    items: months.map((month) {
                      return DropdownMenuItem(
                        value: month,
                        child: Text(month),
                      );
                    }).toList(),
                    onChanged: (value) {
                      setState(() {
                        selectedMonthName = value!;
                        selectedMonth = 'filtered';
                        fetchStatistics();
                      });
                    },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DropdownButton<int>(
                    isExpanded: true,
                    value: selectedYear,
                    items: years.map((year) {
                      return DropdownMenuItem(
                        value: year,
                        child: Text(year.toString()),
                      );
                    }).toList(),
                    onChanged: (value) {
                      setState(() {
                        selectedYear = value!;
                        selectedMonth = 'filtered';
                        fetchStatistics();
                      });
                    },
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.clear),
                  tooltip: 'Clear filter',
                  onPressed: () {
                    setState(() {
                      selectedMonth = 'All';
                      fetchStatistics();
                    });
                  },
                ),
              ],
            ),

            const SizedBox(height: 16),

            PieChart(
              dataMap: chartData.isEmpty ? {'No Data': 1} : chartData,
              chartType: ChartType.disc,
              chartRadius: 150,
              colorList: const [Colors.cyanAccent, Colors.blueAccent, Colors.teal],
              chartValuesOptions: const ChartValuesOptions(showChartValuesInPercentage: true),
            ),

            const SizedBox(height: 20),

            Expanded(
              child: expenses.isEmpty
                  ? const Center(child: Text('No data available'))
                  : ListView.builder(
                itemCount: expenses.length,
                itemBuilder: (context, index) {
                  final item = expenses[index];
                  return ListTile(
                    leading: Icon(_getIcon(item.category)),
                    title: Text(item.category),
                    subtitle: Text(DateFormat('EEE, d MMM').format(DateTime.parse(item.date))),
                    trailing: Text(
                      "-RM ${item.amount.toStringAsFixed(2)}",
                      style: const TextStyle(color: Colors.red),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class Statistic {
  final String category;
  final double amount;
  final String date;

  Statistic({
    required this.category,
    required this.amount,
    required this.date,
  });

  factory Statistic.fromJson(Map<String, dynamic> json) {
    return Statistic(
      category: json['category'],
      amount: double.tryParse(json['amount'].toString()) ?? 0.0,
      date: json['date'],
    );
  }
}
