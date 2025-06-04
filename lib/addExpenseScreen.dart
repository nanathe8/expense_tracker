import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'config.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';  // For image picking
import 'dart:io';  // For File class
import 'package:flutter_tesseract_ocr/flutter_tesseract_ocr.dart';  // For OCR text extraction

class AddExpenseScreen extends StatefulWidget {
  const AddExpenseScreen({Key? key}) : super(key: key);

  @override
  _AddExpenseScreenState createState() => _AddExpenseScreenState();
}

class _AddExpenseScreenState extends State<AddExpenseScreen> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _amountController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final TextEditingController _dateController = TextEditingController();
  String? selectedCategory;
  File? _receiptImage;
  final picker = ImagePicker();  // Image Picker
  List<dynamic> categories = [];  // List to hold categories
  String _ocrText = "";  // Store OCR text from the image

  // Declare the groupName and role variables to store the group and role info
  String? groupName;
  String? role;

  Future<String?> getSessionId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? sessionId = prefs.getString('session_id');  // Retrieve the session ID
    print('Session ID retrieved: $sessionId');  // Debugging: Check if session ID is correctly retrieved
    return sessionId;
  }

  // Fetch categories and groups from the server
  Future<void> fetchCategoriesAndGroups() async {
    try {
      final sessionId = await getSessionId();
      if (sessionId == null) {
        print("User is not logged in");
        return;
      }

      final categoryResponse = await http.get(
        Uri.parse('${CONFIG.SERVER}/getCategories.php'),
        headers: {
          'Cookie': 'PHPSESSID=$sessionId',  // Sending session ID as a cookie in headers
        },
      );

      final groupResponse = await http.get(
        Uri.parse('${CONFIG.SERVER}/getUserGroups.php'),
        headers: {
          'Cookie': 'PHPSESSID=$sessionId',  // Sending session ID as a cookie in headers
        },
      );

      print('Category Response: ${categoryResponse.body}');
      print('Group Response: ${groupResponse.body}');

      if (categoryResponse.statusCode == 200 && groupResponse.statusCode == 200) {
        final categoryData = json.decode(categoryResponse.body);
        final groupData = json.decode(groupResponse.body);

        if (groupData['status'] == 'error') {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(groupData['message'])));
          return;
        }

        setState(() {
          categories = categoryData['categories'];  // Set categories
          groupName = groupData['groupName'];  // Set group name
          role = groupData['role'];  // Set role
        });
      } else {
        throw Exception('Failed to load categories or groups');
      }
    } catch (e) {
      print('Error fetching categories or groups: $e');
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
    }
  }

  // Pick image for the receipt
  Future<void> _pickImage() async {
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);
    if (pickedFile != null) {
      setState(() {
        _receiptImage = File(pickedFile.path);
      });

      // Extract text from the selected image using Tesseract OCR
      String text = await FlutterTesseractOcr.extractText(pickedFile.path);
      setState(() {
        _ocrText = text;  // Store OCR text
        _descriptionController.text = text;  // Auto-fill description with OCR text
        _autoFillFields();  // Auto-fill amount and date
      });
    }
  }

  // Auto-fill amount and date using OCR text
  void _autoFillFields() {
    if (_ocrText.isNotEmpty) {
      _extractAmount(_ocrText);
      _extractDate(_ocrText);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("No OCR text found.")));
    }
  }

  // Extract Amount from OCR text
  void _extractAmount(String text) {
    RegExp amountRegExp = RegExp(r'Total\s?Amount\s?:?\s?([0-9]+\.[0-9]{2})');  // Capture total amount
    var match = amountRegExp.firstMatch(text);
    if (match != null) {
      setState(() {
        _amountController.text = 'RM ${match.group(1)}';  // Auto-fill amount
      });
    }
  }

  // Extract Date from OCR text
  void _extractDate(String text) {
    RegExp dateRegExp = RegExp(r'(\d{2}/\d{2}/\d{4})');  // Capture date (dd/mm/yyyy)
    var match = dateRegExp.firstMatch(text);
    if (match != null) {
      setState(() {
        _dateController.text = match.group(0)!;  // Auto-fill date
      });
    }
  }

  // Function to select a date using a calendar
  Future<void> _selectDate(BuildContext context) async {
    final DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2101),
    );
    if (pickedDate != null && pickedDate != DateTime.now()) {
      setState(() {
        _dateController.text = pickedDate.toLocal().toString().split(' ')[0]; // Format date as 'yyyy-MM-dd'
      });
    }
  }

  // Submit the form with the selected data
  Future<void> _submitForm() async {
    if (_formKey.currentState!.validate() && selectedCategory != null) {
      final amount = _amountController.text;
      final description = _descriptionController.text;
      final date = _dateController.text;

      final uri = Uri.parse('${CONFIG.SERVER}/addGroupExpense.php'); // Your PHP file URL
      var request = http.MultipartRequest('POST', uri);

      // Add fields to the request
      request.fields['category_id'] = selectedCategory!;
      request.fields['amount'] = amount;
      request.fields['description'] = description;
      request.fields['date'] = date;

      // If a receipt is selected, add it to the request
      if (_receiptImage != null) {
        var pic = await http.MultipartFile.fromPath('receipt_image', _receiptImage!.path);
        request.files.add(pic);
      }

      // Send the request
      try {
        var response = await request.send();
        if (response.statusCode == 200) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Expense added successfully!")));
        } else {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Failed to add expense")));
        }
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
      }
    }
  }

  @override
  void initState() {
    super.initState();
    fetchCategoriesAndGroups();  // Fetch categories and groups when the screen loads
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Add Expense'),
      ),
      body: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 24.0),
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                // Receipt Image Upload Button
                ElevatedButton(
                  onPressed: _pickImage,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.deepPurple,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: const Text("Upload Receipt Image", style: TextStyle(fontSize: 16)),
                ),
                const SizedBox(height: 20),

                // Display Uploaded Receipt Image
                if (_receiptImage != null)
                  Card(
                    elevation: 5,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: Image.file(
                        _receiptImage!,
                        height: 150,
                        width: double.infinity,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                const SizedBox(height: 20),

                // Category Selection
                Text(
                  "Select Category",
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 10),
                DropdownButtonFormField<String>(
                  value: selectedCategory,
                  hint: const Text('Select Category'),
                  items: categories.map((category) {
                    return DropdownMenuItem<String>(
                      value: category['categoryID'].toString(),
                      child: Text(category['categoryName']),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setState(() {
                      selectedCategory = value;
                    });
                  },
                  validator: (value) {
                    if (value == null) {
                      return 'Please select a category';
                    }
                    return null;
                  },
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: Colors.white.withOpacity(0.9),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    contentPadding: EdgeInsets.symmetric(vertical: 18.0, horizontal: 16.0),
                  ),
                ),
                const SizedBox(height: 20),

                // Amount Input
                TextFormField(
                  controller: _amountController,
                  decoration: InputDecoration(
                    labelText: 'Amount (RM)',
                    filled: true,
                    fillColor: Colors.white.withOpacity(0.9),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    contentPadding: EdgeInsets.symmetric(vertical: 18.0, horizontal: 16.0),
                  ),
                  keyboardType: TextInputType.number,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter an amount';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),

                // Description Input (Auto-filled via OCR)
                TextFormField(
                  controller: _descriptionController,
                  decoration: InputDecoration(
                    labelText: 'Description',
                    filled: true,
                    fillColor: Colors.white.withOpacity(0.9),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    contentPadding: EdgeInsets.symmetric(vertical: 18.0, horizontal: 16.0),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter a description';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),

                // Date Input with Calendar
                TextFormField(
                  controller: _dateController,
                  decoration: InputDecoration(
                    labelText: 'Date',
                    filled: true,
                    fillColor: Colors.white.withOpacity(0.9),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    contentPadding: EdgeInsets.symmetric(vertical: 18.0, horizontal: 16.0),
                    suffixIcon: IconButton(
                      icon: Icon(Icons.calendar_today),
                      onPressed: () => _selectDate(context), // Trigger date picker
                    ),
                  ),
                  keyboardType: TextInputType.datetime,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter a date';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),

                // Submit Button
                ElevatedButton(
                  onPressed: _submitForm,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.deepPurple,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: const Text("Add Expense", style: TextStyle(fontSize: 16)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
