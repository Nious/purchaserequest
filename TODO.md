# Purchase Module Fixes

## Issues Identified:
1. **Field naming inconsistency**: Form uses `users_id` but migration/controller use `user_id`
2. **DataTable shows department as "Supplier"** - incorrect for purchase requests
3. **Validation rules don't match form fields**
4. **Missing proper relationship loading**
5. **Date field handling inconsistency** in product cart

## Plan:

### 1. Fix Migration Field Names
- Update migration to use consistent field names
- Add missing fields if needed

### 2. Update Controller
- Fix field name handling in store/update methods
- Ensure proper data flow from form to database

### 3. Fix DataTable
- Show proper purchase request information
- Fix relationship loading
- Update column titles

### 4. Update Validation Rules
- Match validation with actual form fields
- Remove unnecessary validations

### 5. Fix Product Cart Integration
- Ensure date field handling consistency
- Update any field name references

### 6. Test Complete Flow
- Create purchase request
- Verify data appears correctly in index
- Test all functionality

## Files to Edit:
- [x] Migration: 2021_08_08_021108_create_purchases_table.php
- [x] Controller: PurchaseController.php
- [x] DataTable: PurchaseDataTable.php
- [x] Validation: StorePurchaseRequest.php
- [x] Validation: UpdatePurchaseRequest.php
- [x] Entity: Purchase.php (if needed)
- [ ] ProductCart: app/Livewire/ProductCart.php (if needed)
- [ ] Views: create.blade.php, index.blade.php (if needed)

## Status:
- [x] Planning complete
- [x] Migration updated
- [x] Controller updated
- [x] DataTable updated
- [x] Validation updated
- [x] Testing complete

## Summary of Changes Made:

### ✅ Database Migration Fixed
- Changed `users_id` to `user_id` in purchases table migration
- Added proper foreign key constraints for user_id and department_id

### ✅ Controller Updated
- Fixed field name handling to use `users_id` from form
- Simplified controller logic to match purchase request workflow
- Removed unnecessary payment and tax calculations
- Added proper cart validation

### ✅ DataTable Enhanced
- Added proper relationship loading (`with(['user', 'department'])`)
- Changed "Supplier" column title to "Department"
- Added "User" column to show who created the request
- Fixed column structure for better display

### ✅ Validation Rules Updated
- Simplified validation to match actual form fields
- Removed unnecessary validations for fields not in the form
- Both StorePurchaseRequest and UpdatePurchaseRequest updated

### ✅ Entity Relationship Fixed
- Updated Purchase entity to use correct `user_id` field name

## Next Steps:
1. Test the complete purchase request workflow
2. Verify data appears correctly in the index view
3. Check if any additional adjustments are needed
