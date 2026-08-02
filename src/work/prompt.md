You are a Senior ERP Solution Architect, Senior Laravel Developer, Senior UI/UX Designer, and Database Architect.

I want you to design and build a complete Garments Inventory Management System for a garments factory.

IMPORTANT RULES

- This is NOT a simple stock management system.
- This should follow a real garments factory workflow.
- The system must be modular, scalable, and ERP-ready.
- UI should be modern, clean, professional, and very easy for store personnel.
- Every module should have proper workflow, validation, permissions, and reports.
- Think like commercial ERP software.

===================================
SYSTEM OVERVIEW
===================================

The system should manage:

1. Raw Materials
    - Fabric
    - Rib
    - Thread
    - Button
    - Zipper
    - Elastic
    - Labels
    - Poly
    - Carton
    - Accessories

2. Work In Progress (WIP)

3. Finished Goods

4. Multiple Stores

5. Department Transfers

6. Shipment

7. Inventory Reports

===================================
MAIN MODULES
===================================

1. Dashboard

Show

- Total Items
- Total Stock Value
- Today's Goods Receive
- Today's Issue
- Pending Requisition
- Pending Gate Pass
- Low Stock
- Recent Activities

===================================

2. User Management

Roles

- Super Admin
- Admin
- Store Manager
- Store Keeper
- Purchase
- Production
- Cutting
- Sewing
- Finishing
- Commercial
- Viewer

Permission-based access.

===================================

3. Store Management

Multiple Stores

Examples

- Main Raw Material Store
- Accessories Store
- Cutting Store
- Sewing Store
- Finishing Store
- Finished Goods Store

===================================

4. Item Master

Item Code (Auto)

Item Name

Category

Sub Category

Unit

Brand

Color

Size

Specification

Minimum Stock

Maximum Stock

Opening Stock

Opening Value

Status

Barcode / QR Support

===================================

5. Supplier Management

Supplier Information

Address

Contact

TIN/VAT

Remarks

===================================

6. Purchase Order

PO Number

Supplier

Items

Quantity

Rate

Status

Draft

Approved

Received

Closed

===================================

7. Goods Receive Note (GRN)

Receive against PO.

Receive Quantity

Lot

Batch

Remarks

GRN automatically updates stock.

===================================

8. Main Store Inventory

Current Stock

Reserved Stock

Available Stock

Stock Value

===================================

9. Store Requisition

Departments create requisitions.

Departments

Cutting

Sewing

Finishing

Packing

Maintenance

Admin

Fields

Requisition No

Date

Requested By

Department

Items

Requested Qty

Status

Pending

Approved

Rejected

Issued

===================================

10. Approval Workflow

Approval by Store Manager/Admin.

Approval History

Remarks

===================================

11. Store Issue

Issue against approved requisition.

Issue Slip

Issued By

Received By

Partial Issue

Full Issue

Stock deduction automatically.

===================================

12. Department Receive

Department confirms receipt.

Partial Receive

Full Receive

===================================

13. Internal Stock Transfer

Store to Store Transfer.

Transfer Request

Transfer Approval

Transfer Receive

===================================

14. Production Consumption

Consume raw materials.

Track

Consumed Qty

Waste Qty

Balance

===================================

15. Finished Goods Receive

Receive finished garments.

Style

Color

Size

Order

Buyer

Quantity

===================================

16. Gate Pass

Gate Pass Number

Buyer

Vehicle

Driver

Remarks

Issue Finished Goods

===================================

17. Shipment

Shipment No

Buyer

Invoice

Packing List

Status

Pending

Dispatched

Delivered

===================================

18. Stock Adjustment

Damage

Lost

Excess

Physical Count

Adjustment History

Approval Required

===================================

19. Stock Ledger

Every stock movement must be recorded.

Movement Types

Opening

Purchase

GRN

Issue

Transfer

Receive

Adjustment

Production

Finished Goods

Shipment

Gate Pass

===================================

20. Reports

Current Stock

Stock Ledger

Stock Summary

Item History

Store Wise Stock

Department Wise Consumption

Supplier Wise Purchase

GRN Report

Issue Report

Gate Pass Report

Shipment Report

Low Stock Report

Dead Stock Report

Stock Valuation

===================================
DATABASE DESIGN
===================================

Design a complete normalized database.

Include

ER Diagram

Primary Keys

Foreign Keys

Indexes

Constraints

Soft Deletes

Audit Fields

===================================
WORKFLOW
===================================

Purchase Order

↓

Goods Receive (GRN)

↓

Main Store

↓

Store Requisition

↓

Approval

↓

Store Issue

↓

Department Receive

↓

Production Consumption

↓

Finished Goods Receive

↓

Finished Goods Store

↓

Gate Pass

↓

Shipment

Every transaction must update inventory automatically.

===================================
INVENTORY ENGINE
===================================

Implement inventory using transaction-based architecture.

Do NOT store current stock manually.

Current stock must always be calculated from stock transactions.

Maintain complete audit history.

===================================
REPORTING
===================================

Provide powerful filtering.

Date Range

Store

Category

Department

Supplier

Buyer

Item

Status

===================================
UI REQUIREMENTS
===================================

Modern ERP Dashboard

Responsive Design

Sidebar Navigation

Breadcrumb

Search

Advanced Filters

Data Tables

Export Excel

Export PDF

Print

Dark Mode Ready

Minimal Clicks

Professional UX

===================================
TECHNICAL REQUIREMENTS
===================================

Use

Laravel 12

PHP 8.3+

MySQL

Blade

Bootstrap 5

JavaScript

AJAX

RESTful Architecture

Repository Pattern

Service Layer

Form Requests

Policies

Authentication

Role Permission

Activity Log

Notification System

===================================
DELIVERABLES
===================================

Generate the project in phases.

Phase 1
- System Architecture
- Folder Structure
- Database Design
- ER Diagram
- Module Relationship

Phase 2
- Database Migration
- Models
- Relationships

Phase 3
- Authentication
- Roles
- Permissions

Phase 4
- CRUD for every module

Phase 5
- Inventory Engine

Phase 6
- Reports

Phase 7
- Dashboard

Phase 8
- UI Polish

Phase 9
- Testing

Do NOT skip any phase.

Before writing code, explain the architecture and database in detail.

Always generate production-ready code with proper comments, validation, clean architecture, and best practices.