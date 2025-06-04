import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'config.dart';  // Assuming you have a config.dart for the server URL

class AddCategoryScreen extends StatefulWidget {
  const AddCategoryScreen({Key? key}) : super(key: key);

  @override
  _AddCategoryScreenState createState() => _AddCategoryScreenState();
}

class _AddCategoryScreenState extends State<AddCategoryScreen> {
  final _categoryController = TextEditingController();
  String? selectedIcon;

  // Function to add the new category to the database
  Future<void> addCategory() async {
    final categoryName = _categoryController.text;

    if (categoryName.isEmpty || selectedIcon == null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Please fill in all fields')));
      return;
    }

    final url = '${CONFIG.SERVER}/addCategory.php';  // Your API endpoint

    try {
      final response = await http.post(
        Uri.parse(url),
        body: {
          'category_name': categoryName,
          'icon': selectedIcon!,  // You can add more fields if necessary
        },
      );

      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Category added successfully!')));
        Navigator.pop(context);  // Close the screen after success
      } else {
        throw Exception('Failed to add category');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Add New Category"),
        backgroundColor: Colors.deepPurple,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: <Widget>[
            // Category Name Input
            TextField(
              controller: _categoryController,
              decoration: InputDecoration(
                labelText: 'Category Name',
                filled: true,
                fillColor: Colors.white.withOpacity(0.9),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Icon Selector
            Text(
              'Select Icon',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 10),
            DropdownButton<String>(
              value: selectedIcon,
              hint: const Text('Choose an icon'),
              items: [
                DropdownMenuItem<String>(
                  value: 'food',
                  child: Row(
                    children: [
                      Icon(Icons.fastfood),
                      const SizedBox(width: 10),
                      Text('Food'),
                    ],
                  ),
                ),
                DropdownMenuItem<String>(
                  value: 'shopping',
                  child: Row(
                    children: [
                      Icon(Icons.shopping_cart),
                      const SizedBox(width: 10),
                      Text('Shopping'),
                    ],
                  ),
                ),
                // Add more categories as needed
              ],
              onChanged: (value) {
                setState(() {
                  selectedIcon = value;
                });
              },
            ),
            const SizedBox(height: 20),

            // Add Category Button
            ElevatedButton(
              onPressed: addCategory,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.deepPurple,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
              child: const Text("Add Category", style: TextStyle(fontSize: 16)),
            ),
          ],
        ),
      ),
    );
  }
}
