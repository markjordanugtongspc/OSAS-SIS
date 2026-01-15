# Borrow Management - Inventory Tracking System

## 📦 How Inventory Updates Work

### 1️⃣ **Creating a Borrow Request**
**Status:** Pending  
**Inventory Impact:** ❌ No change  
- Request is created but inventory is **NOT deducted** yet
- Waiting for approval from administrator

### 2️⃣ **Approving a Borrow Request**
**Status:** Pending → Approved  
**Inventory Impact:** ✅ Quantity DECREASED  

**What Happens:**
```php
// Deduct borrowed quantity from inventory
UPDATE items SET quantity = quantity - [borrowed_qty] WHERE id = [item_id]

// If quantity becomes 0, mark as Unavailable
UPDATE items SET status = 'Unavailable' WHERE id = [item_id] AND quantity = 0
```

**Example:**
- Basketball quantity: 10
- User borrows: 3
- **New quantity: 7** ✅

### 3️⃣ **Returning an Item**
**Status:** Approved → Returned  
**Inventory Impact:** ✅ Quantity INCREASED  

**What Happens:**
```php
// Add borrowed quantity back to inventory
UPDATE items SET quantity = quantity + [borrowed_qty] WHERE id = [item_id]

// If item was Unavailable, mark as Available
UPDATE items SET status = 'Available' WHERE id = [item_id] AND quantity > 0
```

**Example:**
- Basketball quantity: 7
- User returns: 3
- **New quantity: 10** ✅ (Back to original!)

### 4️⃣ **Deleting a Borrow Record**
**Inventory Impact:** ✅ Quantity RESTORED (if approved)  

**What Happens:**

**If Status = Approved:**
```php
// Restore the borrowed quantity
UPDATE items SET quantity = quantity + [borrowed_qty] WHERE id = [item_id]

// Update status if was Unavailable
UPDATE items SET status = 'Available' WHERE id = [item_id] AND quantity > 0

// Soft delete the record
UPDATE borrow_lists SET deleted_at = NOW() WHERE id = [borrow_id]
```

**If Status = Pending/Rejected:**
```php
// Just soft delete (no inventory change needed)
UPDATE borrow_lists SET deleted_at = NOW() WHERE id = [borrow_id]
```

**Example:**
- User deletes approved borrow of 3 basketballs
- Quantity: 7 → **10** ✅ (Restored!)

### 5️⃣ **Rejecting a Borrow Request**
**Status:** Pending → Rejected  
**Inventory Impact:** ❌ No change  
- Request was never approved, so inventory was never deducted

---

## 🔄 Complete Flow Example

### Scenario: Borrowing 5 Volleyballs

| Action | Status | Inventory Quantity | Status |
|--------|--------|-------------------|--------|
| **Initial State** | - | 15 | Available |
| Create Request | Pending | 15 | Available ✨ No change |
| **Approve Request** | Approved | **10** ⬇️ | Available |
| Continue borrowing | Approved | 10 | Available |
| **Return Item** | Returned | **15** ⬆️ | Available |

### Scenario: Deleting Approved Borrow

| Action | Status | Inventory Quantity | Status |
|--------|--------|-------------------|--------|
| **Initial State** | Approved | 10 | Available |
| **Delete Record** | Deleted | **15** ⬆️ | Available |
| Result | - | 15 | Available ✅ Restored! |

---

## ⚠️ Important Notes

1. **Only Approved borrows** affect inventory
2. **Pending/Rejected borrows** never touch inventory
3. **Deleting approved borrows** restores the quantity
4. **Returning items** always restores quantity
5. **All operations use transactions** to ensure data integrity
6. **Status automatically updates** (Available/Unavailable based on quantity)

---

## 🎯 Summary

✅ **Return** → Quantity restored  
✅ **Delete (Approved)** → Quantity restored  
✅ **Delete (Pending/Rejected)** → No inventory change  
✅ **Approve** → Quantity deducted  
❌ **Create** → No inventory change (waiting for approval)  
❌ **Reject** → No inventory change  

Your inventory is always automatically synchronized with borrow records! 🎉
