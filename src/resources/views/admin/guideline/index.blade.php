@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('User Guideline') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1">
    @include('sfl-inventory::admin.partials.alerts')

<style>
.gd-hero { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 14px; padding: 28px 26px; color: #fff; margin-bottom: 22px; box-shadow: 0 4px 18px rgba(249,115,22,.25); }
.gd-hero h3 { font-weight: 800; margin-bottom: 6px; }
.gd-hero p { margin-bottom: 0; opacity: .95; font-size: 14px; }
.gd-toc { background:#fff; border-radius: 12px; padding: 18px; box-shadow: 0 2px 12px rgba(0,0,0,.07); position: sticky; top: 15px; }
.gd-toc a { display:block; padding: 6px 10px; border-radius: 8px; color:#444; text-decoration:none; font-size: 13px; margin-bottom: 2px; }
.gd-toc a:hover { background:#fff3ea; color:#ea580c; }
.gd-card { background:#fff; border-radius: 12px; padding: 22px 24px; box-shadow: 0 2px 12px rgba(0,0,0,.07); margin-bottom: 20px; scroll-margin-top: 15px; }
.gd-card h4 { font-weight: 800; color:#1f2937; border-left: 4px solid #f97316; padding-left: 12px; margin-bottom: 6px; }
.gd-card .gd-path { font-size: 12px; color:#ea580c; background:#fff3ea; display:inline-block; padding: 3px 10px; border-radius: 6px; margin-bottom: 12px; font-weight: 600; }
.gd-card p, .gd-card li { font-size: 14px; color:#374151; line-height: 1.7; }
.gd-note { background:#fff8ed; border-left: 4px solid #f97316; border-radius: 8px; padding: 14px 16px; font-size: 13.5px; color:#7c3e0a; }
.gd-flow { background:#111827; color:#e5e7eb; border-radius: 12px; padding: 20px; font-size: 13px; line-height: 2; white-space: pre; overflow-x:auto; }
.gd-flow b { color:#fbbf24; }
.gd-table { width:100%; font-size:13.5px; }
.gd-table th { background:#fff3ea; color:#ea580c; }
.gd-subhead { font-weight:700; color:#111827; margin-top: 14px; margin-bottom: 6px; font-size: 14.5px; }
</style>

    <div class="gd-hero">
        <h3><i class="fa-solid fa-book-open"></i> Inventory Management — User Guideline</h3>
        <p>ইনভেন্টরি সম্পর্কে আগে থেকে কিছু জানার দরকার নেই — প্রতিটা পেজ কী কাজ করে, কেন দরকার, কীভাবে ব্যবহার করবেন তা এখানে সহজ ভাষায় লেখা আছে।</p>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="gd-toc">
                <div class="gd-subhead" style="margin-top:0;"><i class="fa-solid fa-list"></i> সূচিপত্র</div>
                <a href="#concept"><i class="fa-solid fa-key text-warning"></i> মূল কনসেপ্ট</a>
                <a href="#dashboard"><i class="fa-solid fa-gauge text-primary"></i> ১. Dashboard</a>
                <a href="#masters"><i class="fa-solid fa-gear text-secondary"></i> ২. Masters</a>
                <a href="#purchase"><i class="fa-solid fa-cart-shopping text-warning"></i> ৩. Purchase &amp; GRN</a>
                <a href="#overview"><i class="fa-solid fa-boxes-packing text-success"></i> ৪. Main Store Inventory</a>
                <a href="#reqissue"><i class="fa-solid fa-clipboard-list text-primary"></i> ৫. Requisition &amp; Issue</a>
                <a href="#transfer"><i class="fa-solid fa-right-left text-info"></i> ৬. Stock Transfer</a>
                <a href="#production"><i class="fa-solid fa-industry text-danger"></i> ৭. Production Consumption</a>
                <a href="#finishedgoods"><i class="fa-solid fa-box-open text-success"></i> ৮. Finished Goods</a>
                <a href="#adjustment"><i class="fa-solid fa-scale-unbalanced text-danger"></i> ৯. Stock Adjustment</a>
                <a href="#ledger"><i class="fa-solid fa-book text-secondary"></i> ১০. Stock Ledger</a>
                <a href="#reports"><i class="fa-solid fa-chart-line text-info"></i> ১১. Reports</a>
                <a href="#workflow"><i class="fa-solid fa-diagram-project"></i> পুরো ওয়ার্কফ্লো</a>
                <a href="#permission"><i class="fa-solid fa-user-shield"></i> Permission</a>
            </div>
        </div>

        <div class="col-md-9">

            <div class="gd-card" id="concept">
                <h4>সবচেয়ে গুরুত্বপূর্ণ কনসেপ্ট: "Current Stock" কখনো সরাসরি বসানো থাকে না</h4>
                <div class="gd-note">
                    কোনো আইটেমের বর্তমান স্টক কোথাও সরাসরি সংখ্যা হিসেবে বসানো নেই। প্রতিটা লেনদেন (GRN, Issue, Transfer, ইত্যাদি) একটা লম্বা খাতায় (<b>Ledger / Stock Transactions</b>) লেখা হয়, আর বর্তমান স্টক সবসময় সেই খাতা থেকে যোগ-বিয়োগ করে বের করা হয় — ঠিক যেমন ব্যাংক পাসবই থেকে ব্যালেন্স বের করা হয়।
                </div>
                <p class="mt-3 mb-0"><strong>সহজ কথায়:</strong> Store-এ কিছু ঢুকলে "IN" লেখা হয়, বের হলে "OUT" লেখা হয়। যেকোনো সময় <code>স্টক = সব IN এর যোগফল − সব OUT এর যোগফল</code>।</p>
            </div>

            <div class="gd-card" id="dashboard">
                <h4><i class="fa-solid fa-gauge text-primary"></i> ১. Dashboard</h4>
                <span class="gd-path">Inventory Management → Dashboard</span>
                <p>পুরো সিস্টেমের সামারি পেজ — প্রথমেই এটা খুলবে। এখানে দেখা যায়:</p>
                <ul>
                    <li>মোট আইটেম সংখ্যা, মোট স্টক ভ্যালু</li>
                    <li>আজকের GRN (মাল ঢোকা) ও Issue (মাল বের হওয়া) কার্যক্রম</li>
                    <li>Pending Requisition / Store Order / Gate Pass — যেগুলো অ্যাপ্রুভালের অপেক্ষায়</li>
                    <li>Low Stock আইটেমের তালিকা — যেগুলো আবার কিনতে হবে</li>
                    <li>গত ৩০ দিন ও ৬ মাসের মুভমেন্ট চার্ট, ক্যাটাগরি/স্টোর অনুযায়ী ব্রেকডাউন</li>
                </ul>
            </div>

            <div class="gd-card" id="masters">
                <h4><i class="fa-solid fa-gear text-secondary"></i> ২. Masters (মাস্টার ডেটা)</h4>
                <span class="gd-path">Masters → ...</span>
                <p>মাস্টার মানে এমন তথ্য যা বারবার ব্যবহার হবে কিন্তু কম পরিবর্তন হয়। নতুন লেনদেন করার আগে এগুলো একবার সেটআপ করতে হয়।</p>
                <div class="gd-subhead">Stores</div>
                <p>গুদাম/স্টোরের তালিকা (Main Store, Cutting/Sewing/Finishing Floor Store)। প্রতিটার স্টক আলাদা রাখা হয়।</p>
                <div class="gd-subhead">Item Categories</div>
                <p>আইটেম গ্রুপ করার জায়গা — Sewing, Cutting, Finishing, Packaging, Trims, Office Supply ইত্যাদি। প্রতিটার একটা কোড থাকে (SW, CT, FN) যা আইটেম কোডে ব্যবহার হয়।</p>
                <div class="gd-subhead">Units / Brands / Colors / Sizes</div>
                <p>একক (PCS, PKT, Roll) এবং আইটেমের অতিরিক্ত বৈশিষ্ট্য — অপশনাল।</p>
                <div class="gd-subhead">Items (আইটেম মাস্টার) — সবচেয়ে গুরুত্বপূর্ণ</div>
                <p>প্রতিটা কাঁচামালের নিজস্ব রেকর্ড — ইউনিক কোড (যেমন <code>SFL-SW-085</code>) সিস্টেম নিজেই বানায়। Category/Unit বাধ্যতামূলক, Department/Supplier/Buyer অপশনাল। Minimum/Maximum Stock বসালে Low Stock এ দেখাবে।</p>
                <div class="gd-subhead">Suppliers / Buyers / Departments</div>
                <p>যথাক্রমে — কার কাছ থেকে কেনা হয়, কার অর্ডারের জন্য প্রোডাকশন, কোন ফ্লোর সেকশন (Cutting/Sewing/Finishing/Sample/Office...)।</p>
                <div class="gd-subhead">Operators / Store Incharge</div>
                <p>কে কোন স্টোরের দায়িত্বে — সাধারণ Operator শুধু নিজের এন্ট্রি দেখে, Store Incharge/Manager তার স্টোরের সব এন্ট্রি দেখে।</p>
            </div>

            <div class="gd-card" id="purchase">
                <h4><i class="fa-solid fa-cart-shopping text-warning"></i> ৩. Purchase (কেনাকাটা)</h4>
                <div class="gd-subhead">Store Order (SO)</div>
                <span class="gd-path">Purchase → Store Order</span>
                <p>সাপ্লায়ারকে অর্ডার দেওয়ার এন্ট্রি (মাল এখনো হাতে আসেনি) — Approve করতে হয়। প্রতিটা PO-র পাশে <strong>"Challans (N)"</strong> বাটনে ক্লিক করলে সেই PO-র বিপরীতে কতবার চালান এসেছে তা এক এক করে (Challan #1, #2...) দেখা যায় — কোন চালানে কোন প্রোডাক্ট কত পরিমাণ এসেছে।</p>
                <div class="gd-note">একটা PO-র মাল একবারে না এসে কয়েক দফায় (কয়েকটা চালানে) আসতে পারে — যেমন ১০০ পিস অর্ডারের প্রথম চালানে ৪০ পিস, পরের চালানে ৬০ পিস। সিস্টেম প্রতিটা চালান আলাদাভাবে ট্র্যাক করে, বাকি (Remaining) কতটুকু আছে দেখায়, এবং পুরো অর্ডার সম্পূর্ণ হলে PO স্ট্যাটাস <strong>"Closed"</strong> হয়ে যায়।</div>
                <div class="gd-subhead">Goods Receive (GRN) — মাল স্টোরে ঢোকানো</div>
                <span class="gd-path">Purchase → Goods Receive (GRN)</span>
                <p>GRN পোস্ট করলেই স্টক বেড়ে যায় (Ledger-এ IN)। "Add GRN" এ ক্লিক করলে উপরে <strong>Receipt Type</strong> দেখাবে:</p>
                <ul>
                    <li><strong>Purchase</strong> — PO বা সরাসরি Supplier থেকে কেনা মাল রিসিভ (Supplier সিলেক্ট করতে হয়)</li>
                    <li><strong>Buyer Supplied</strong> — বায়ার নিজে ফেব্রিক/এক্সেসরিজ পাঠিয়েছে, কোনো কেনাকাটা হয়নি (Supplier লাগবে না, Buyer/Style/Order Ref বসাতে হয়)</li>
                </ul>
                <p>GRN লিস্টে "Source" কলাম দেখে বোঝা যায় কোনটা Purchase আর কোনটা Buyer Supplied।</p>
            </div>

            <div class="gd-card" id="overview">
                <h4><i class="fa-solid fa-boxes-packing text-success"></i> ৪. Main Store Inventory</h4>
                <span class="gd-path">Inventory Management → Main Store Inventory</span>
                <p>প্রতিটা আইটেমের বর্তমান অবস্থা লাইভ দেখা যায়:</p>
                <ul>
                    <li><strong>Current Stock</strong> — এখন গুদামে কতটুকু আছে</li>
                    <li><strong>Reserved Stock</strong> — Approved কিন্তু এখনো Issue হয়নি এমন Requisition-এর জন্য বুক করা পরিমাণ</li>
                    <li><strong>Available Stock</strong> — Current − Reserved</li>
                    <li><strong>Stock Value</strong> — গড় দামে (Moving Average) হিসাব করা টাকার মূল্য</li>
                </ul>
            </div>

            <div class="gd-card" id="reqissue">
                <h4><i class="fa-solid fa-clipboard-list text-primary"></i> ৫. Requisition &amp; Issue</h4>
                <div class="gd-subhead">Store Requisitions (চাহিদাপত্র)</div>
                <span class="gd-path">Requisition &amp; Issue → Store Requisitions</span>
                <p>কোনো সেকশনের মাল দরকার হলে Requisition বানায় — Department, Buyer/Style, আইটেম ও পরিমাণ দিয়ে। এরপর Approve/Reject করা হয় (আংশিক পরিমাণও Approve করা যায়)। <strong>Print</strong> বাটনে কাগজে সই করানোর ফরমাল ফর্ম বের হয়।</p>
                <div class="gd-subhead">Store Issues (মাল বরাদ্দ) — Store Delivery Challan</div>
                <span class="gd-path">Requisition &amp; Issue → Store Issues</span>
                <p>Requisition Approve হলে বা সরাসরি "Direct Issue" দিয়ে Issue শুরু হয়। এখানে <strong>৩-ধাপের অ্যাপ্রুভাল সিস্টেম</strong> আছে:</p>
                <ol>
                    <li><strong>Prepared (Pending)</strong> — তৈরি হলো, স্টক এখনো বিয়োগ হয়নি</li>
                    <li><strong>Authorized</strong> — যাচাই হলো, তবুও স্টক বিয়োগ হয়নি</li>
                    <li><strong>Approved</strong> — এই মুহূর্তেই আসল মাল বের হয়, Ledger-এ OUT লেখা হয়</li>
                </ol>
                <p>এরপর সংশ্লিষ্ট সেকশন <strong>"Confirm Receipt"</strong> করে মাল বুঝে পাওয়ার নিশ্চয়তা দেয়। <strong>Print</strong> বাটনে Prepared/Authorized/Approved/Warehouse/Security/Received — সব সইয়ের জায়গাসহ একটা ফরমাল Delivery Challan বের হয়।</p>
            </div>

            <div class="gd-card" id="transfer">
                <h4><i class="fa-solid fa-right-left text-info"></i> ৬. Stock Transfer</h4>
                <span class="gd-path">Inventory Management → Stock Transfer</span>
                <p>এক স্টোর থেকে আরেক স্টোরে (যেমন Main Store → Cutting Floor Store) মাল সরানো। ধাপ: Requested → Approved (উৎস থেকে বের) → Received (গন্তব্যে জমা)।</p>
            </div>

            <div class="gd-card" id="production">
                <h4><i class="fa-solid fa-industry text-danger"></i> ৭. Production Consumption</h4>
                <span class="gd-path">Inventory Management → Production Consumption</span>
                <p>ফ্লোর স্টোরে থাকা মাল প্রোডাকশনে খরচ হলে এন্ট্রি হয়। দুইটা আলাদা সংখ্যা রাখা হয়: <strong>Consumed Qty</strong> (আসল ব্যবহার) ও <strong>Waste Qty</strong> (অপচয়) — দুটো মিলিয়ে স্টক বিয়োগ হয়।</p>
            </div>

            <div class="gd-card" id="finishedgoods">
                <h4><i class="fa-solid fa-box-open text-success"></i> ৮. Finished Goods</h4>
                <div class="gd-subhead">FG Receive</div>
                <p>প্রোডাকশন শেষে তৈরি গার্মেন্টস স্টোরে জমা হওয়ার এন্ট্রি — Buyer/Style অনুযায়ী।</p>
                <div class="gd-subhead">Gate Pass</div>
                <p>তৈরি পণ্য ফ্যাক্টরি থেকে বের করার আগে গাড়ি/ড্রাইভারসহ Gate Pass বানাতে হয়। <strong>Approve/Issue করার মুহূর্তেই</strong> আসল স্টক বের হয়ে যায়।</p>
                <div class="gd-subhead">Shipment</div>
                <p>চূড়ান্ত শিপমেন্ট/এক্সপোর্ট রেকর্ড। Gate Pass-এর সাথে লিংক থাকলে স্টক দ্বিতীয়বার বিয়োগ হয় না; সরাসরি Shipment হলে এখানেই বিয়োগ হয়।</p>
            </div>

            <div class="gd-card" id="adjustment">
                <h4><i class="fa-solid fa-scale-unbalanced text-danger"></i> ৯. Stock Adjustment</h4>
                <span class="gd-path">Inventory Management → Stock Adjustment</span>
                <p>ফিজিক্যাল কাউন্টের সাথে সিস্টেমের হিসাবের গরমিল ঠিক করার জায়গা। System Qty ও Physical Qty বসালে পার্থক্য (Difference) স্বয়ংক্রিয়ভাবে বের হয়; Approve করলে সেই পার্থক্য Ledger-এ পোস্ট হয়ে হিসাব মিলে যায়।</p>
            </div>

            <div class="gd-card" id="ledger">
                <h4><i class="fa-solid fa-book text-secondary"></i> ১০. Stock Ledger</h4>
                <span class="gd-path">Inventory Management → Stock Ledger</span>
                <p>সেই মূল "খাতা" যেখানে প্রতিটা IN/OUT লেনদেন সময়ানুক্রমে লেখা থাকে — এটাই সব হিসাবের ভিত্তি। যেকোনো আইটেমের সম্পূর্ণ ইতিহাস এখানে দেখা যায়।</p>
            </div>

            <div class="gd-card" id="reports">
                <h4><i class="fa-solid fa-chart-line text-info"></i> ১১. Reports</h4>
                <span class="gd-path">Inventory Management → Reports</span>
                <div class="table-responsive">
                    <table class="table table-bordered gd-table">
                        <thead><tr><th>রিপোর্ট</th><th>কী দেখায়</th></tr></thead>
                        <tbody>
                            <tr><td>Store Inventory Report</td><td>আসল স্প্রেডশিটের মতো — আইটেম-ওয়াইজ Stock In, সেকশন-ওয়াইজ Issue, ব্যালেন্স</td></tr>
                            <tr><td>Current Stock</td><td>সব আইটেমের এই মুহূর্তের স্টক</td></tr>
                            <tr><td>Stock Summary</td><td>সংক্ষিপ্ত স্টক সামারি</td></tr>
                            <tr><td>Item History</td><td>নির্দিষ্ট আইটেমের সম্পূর্ণ লেনদেন ইতিহাস</td></tr>
                            <tr><td>Store Wise Stock</td><td>কোন স্টোরে কী পরিমাণ আছে</td></tr>
                            <tr><td>Department Wise Consumption</td><td>কোন সেকশন কত খরচ করেছে</td></tr>
                            <tr><td>Supplier Wise Purchase</td><td>কোন সাপ্লায়ার থেকে কত কেনা হয়েছে</td></tr>
                            <tr><td>GRN Report</td><td>সব মাল রিসিভের রেকর্ড</td></tr>
                            <tr><td>Issue Report</td><td>সব মাল বরাদ্দের রেকর্ড</td></tr>
                            <tr><td>Gate Pass Report</td><td>সব Gate Pass-এর রেকর্ড</td></tr>
                            <tr><td>Shipment Report</td><td>সব শিপমেন্টের রেকর্ড</td></tr>
                            <tr><td>Low Stock Report</td><td>Minimum Stock-এর নিচে নেমে যাওয়া আইটেম</td></tr>
                            <tr><td>Dead Stock Report</td><td>অনেকদিন ব্যবহার না হওয়া আইটেম</td></tr>
                            <tr><td>Stock Valuation</td><td>মোট স্টকের টাকার হিসাব</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mb-0 text-muted" style="font-size:13px;">সব রিপোর্টে সাধারণত তারিখ, স্টোর, ক্যাটাগরি, ডিপার্টমেন্ট, সাপ্লায়ার, বায়ার দিয়ে ফিল্টার এবং Excel/PDF এক্সপোর্ট করা যায়।</p>
            </div>

            <div class="gd-card" id="workflow">
                <h4><i class="fa-solid fa-diagram-project"></i> পুরো ওয়ার্কফ্লো এক নজরে</h4>
                <div class="gd-flow">১. Supplier থেকে কাপড়/এক্সেসরিজ কেনার জন্য <b>Store Order</b> বানানো হলো
        ↓ (Approve)
২. মাল ফ্যাক্টরিতে এলো — <b>GRN</b> দিয়ে স্টোরে ঢোকানো হলো (হয়তো কয়েক দফা চালানে)
        ↓
৩. Cutting সেকশনের কাপড় দরকার — তারা <b>Requisition</b> বানালো
        ↓ (Approve)
৪. স্টোর থেকে <b>Issue</b> বানানো হলো (Prepared → Authorized → Approved)
        ↓ (Approve মুহূর্তেই স্টক থেকে মাল বের হলো)
৫. Cutting সেকশন মাল রিসিভ করে Confirm করলো
        ↓
৬. প্রোডাকশনে কাপড় ব্যবহার হলো — <b>Production Consumption</b> এন্ট্রি হলো
        ↓
৭. তৈরি গার্মেন্টস স্টোরে জমা হলো — <b>FG Receive</b>
        ↓
৮. গার্মেন্টস বের করার জন্য <b>Gate Pass</b> বানানো হলো (Approve করলেই স্টক বের হয়)
        ↓
৯. চূড়ান্ত <b>Shipment</b>/এক্সপোর্ট রেকর্ড করা হলো</div>
                <p class="mt-3 mb-0">প্রতিটা ধাপে Ledger-এ IN/OUT লেখা হচ্ছে, আর যেকোনো সময় Main Store Inventory বা Reports পেজে গিয়ে বর্তমান অবস্থা দেখা যাচ্ছে।</p>
            </div>

            <div class="gd-card mb-0" id="permission">
                <h4><i class="fa-solid fa-user-shield"></i> কে কী দেখতে/করতে পারবে (Permission)</h4>
                <p class="mb-0">প্রতিটা পেজের জন্য আলাদা পারমিশন সেট করা যায় (List/Add/Edit/View/Delete/Approve ইত্যাদি) — Roles &amp; Permission সেটআপ থেকে। যেমন একজন "Cutting Operator" শুধু Cutting-সম্পর্কিত Requisition দেখতে/বানাতে পারবে, কিন্তু Store Order Approve করতে পারবে না। "Store Incharge" তার নিজের স্টোরের সব এন্ট্রি দেখতে পারবে, অন্য স্টোরেরটা না।</p>
            </div>

        </div>
    </div>
</div>
@endsection
