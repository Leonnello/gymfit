# 💰 GymFit Booking Pricing System

## Overview
The booking system now automatically calculates the price based on:
- **Trainer's hourly rate** (stored in database)
- **Duration of session** (calculated from start and end time)

**Formula:** `Total Cost = Hourly Rate × Duration (in hours)`

## Setup

### Step 1: Add Hourly Rate Column to Database

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select your `gymfit` database
3. Go to the SQL tab
4. Copy and paste the contents of `add_pricing_system.sql`
5. Click Execute

**Option B: Using MySQL Command Line**
```sql
ALTER TABLE `users` ADD COLUMN `hourly_rate` DECIMAL(10,2) DEFAULT 500.00 AFTER `role`;
UPDATE `users` SET `hourly_rate` = 500.00 WHERE `role` IN ('trainer', 'trainor');
```

**Option C: Via Browser**
Visit: `http://localhost/gymfit/dashboard/admin/setup_pricing.php`

### Step 2: Verify Setup
1. Login to phpMyAdmin
2. Go to `users` table → Structure
3. You should see `hourly_rate` column with default value 500.00

## How It Works

### For Clients (When Booking)

1. **Select Trainer** → The trainer's hourly rate is fetched
2. **Select Time Slot** → Duration is calculated
3. **Price Automatically Calculated** → Shows:
   - Trainer's Rate (₱X.XX / hour)
   - Duration (X hours)
   - **Total Cost (₱X.XX)**
4. **Book Session** → Price is saved to database

### Example
- Trainer Rate: ₱500/hour
- Session: 2:00 PM to 3:30 PM = 1.5 hours
- Total Cost: ₱500 × 1.5 = **₱750.00**

## Admin: Setting Trainer Rates

### Currently: Default Rate
- All trainers default to **₱500.00 per hour**

### To Change Individual Trainer Rates:
(Admin can edit this in admin panel - update later)

```sql
-- Set specific trainer rate
UPDATE `users` SET `hourly_rate` = 1000.00 WHERE `id` = 14 AND `role` IN ('trainer', 'trainor');
```

## Database Details

### Column Added
- **Table:** `users`
- **Column:** `hourly_rate`
- **Type:** DECIMAL(10,2)
- **Default:** 500.00
- **Position:** After `role` column

### Appointment Amount Saved
- **Table:** `appointments`
- **Column:** `amount` (already exists)
- **Contains:** Total price calculated at booking time

## Files Modified/Created

### New Files
- `dashboard/client/ajax/calculate_price.php` - Price calculation endpoint
- `add_pricing_system.sql` - Database migration
- `dashboard/admin/setup_pricing.php` - Browser-based setup

### Modified Files
- `dashboard/client/client_schedule.php` - Added price display & calculation
- `dashboard/client/book_session.php` - Saves calculated amount

## Price Calculation Endpoint

**URL:** `/dashboard/client/ajax/calculate_price.php`
**Method:** POST
**Parameters:**
- `trainer_id` (int) - Trainer's user ID
- `start_time` (string) - Format: "HH:MM:SS"
- `end_time` (string) - Format: "HH:MM:SS"

**Response:**
```json
{
  "success": true,
  "hourly_rate": 500.00,
  "duration": 1.5,
  "total_price": 750.00,
  "formatted_price": "₱750.00"
}
```

## Features

✅ **Real-time Price Display**
- Price shows immediately when end time is selected
- Updates if trainer or times change

✅ **Automatic Calculation**
- No manual price input needed
- Prevents pricing errors

✅ **Database Persistence**
- Amount saved with appointment
- Can be viewed in booking history

✅ **Flexible Rates**
- Each trainer can have different rate
- Default: ₱500/hour
- Easy to update

## Troubleshooting

### Issue: Price showing ₱0.00
**Solution:**
1. Check if `hourly_rate` column exists:
   - Go to phpMyAdmin → `users` table → Structure
   - You should see `hourly_rate` column
2. If missing, run the SQL setup
3. Refresh the page

### Issue: Price not updating when times change
**Solution:**
1. Make sure both start and end times are selected
2. Verify trainer is selected
3. Check browser console (F12) for errors
4. Try refreshing the page

### Issue: Wrong price displayed
**Solution:**
1. Verify trainer's `hourly_rate` in database
2. Check time calculation (duration in hours)
3. Formula: `hourly_rate × duration = total`

## Future Enhancements

Possible additions:
- Admin panel to edit trainer rates
- Different rates for different training regimes
- Package discounts (multiple sessions)
- Membership pricing tiers
- Automatic payment integration

## Example Scenarios

**Scenario 1: 1-Hour Session**
- Rate: ₱500/hour
- Time: 9:00 AM - 10:00 AM
- Duration: 1 hour
- **Cost: ₱500.00**

**Scenario 2: 2.5-Hour Session**
- Rate: ₱800/hour
- Time: 2:00 PM - 4:30 PM
- Duration: 2.5 hours
- **Cost: ₱2,000.00**

**Scenario 3: 30-Minute Session**
- Rate: ₱600/hour
- Time: 7:00 AM - 7:30 AM
- Duration: 0.5 hours
- **Cost: ₱300.00**

---

**Pricing system is now active! Bookings will automatically calculate the cost.** 💰
