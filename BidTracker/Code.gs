// ============================================================
//  BidTracker Pro — Code.gs  (v10 — Fixed)
// ============================================================
//  Sheet column map (46 columns, indices 0-45):
//  0  ID                    1  Date
//  2  Type                  3  Status
//  4  Description           5  ITB No.
//  6  Agency/Institution    7  Region
//  8  Encoded By            9  Pre-Bid ABC Non-VAT (₱)
//  10 COGS (₱)              11 Deduction 1 (%) — VAT
//  12 Deduction 2 (₱) — EWT 13 Deduction 3 (%) — Commitment Fee
//  14 Deduction 4 (₱)       15 Net (₱)
//  16 Total Deductions (₱)  17 Approved
//  18 Rejected              19 Attended Pre-Bid
//  20 Attendee              21 Bid Docs Bought By
//  22 Bid Docs Price        23 Bid Docs Reviewed By
//  24 Assembling Started    25 Assembling Completed
//  26 Submitted By          27 Submission Date & Time
//  28 Noted By              29 Noted Date & Time
//  30 PO Sent On            31 PO Sent By
//  32 PO Entries (JSON)     33 Deliveries (JSON)
//  34 Collections (JSON)    35 Last Edited By
//  36 Deadline              37 Awarded ABC (₱)
//  38 Comments              39 NOA Received Date
//  40 NTP Received Date     41 PO Received Date
//  42 NOA PDF Link          43 NTP PDF Link
//  44 PO Received PDF Link  45 PO Supplier PDF Link
// ============================================================

var SHEET_NAME  = 'bids';
var NOTIF_SHEET = 'notifications';
var COL_COUNT   = 46;
var DRIVE_FOLDER_ID = '1AveO-m3tkUwmR_YBz3G9TJQp7JoUix_a';

var SLOT_LABELS = ['1st','2nd','3rd','4th','5th','6th','Completed'];

var REGIONS = [
  'NCR','Region I','Region II','Region III','Region IV-A','Region IV-B',
  'Region V','Region VI','Region VII','Region VIII','Region IX','Region X',
  'Region XI','Region XII','Region XIII','BARMM','CAR'
];

var USERS = [
  { username:'emman',    password:'emman',    role:'Admin',   displayName:'Emmanuel'  },
  { username:'bls',      password:'bls',      role:'Admin',   displayName:'BLS Admin' },
  { username:'chairman', password:'chairman', role:'Viewer',  displayName:'Mr. Jay'   },
  { username:'joanne',   password:'joanne',   role:'Viewer',  displayName:'Ms. Jo-Anne' },
  { username:'elaine',   password:'elaine',   role:'Manager', displayName:'Ms. Elaine'  },
  { username:'mayanne',  password:'mayanne',  role:'Manager', displayName:'Ms. May Anne'},
  { username:'ron',      password:'ron',      role:'Manager', displayName:'Mr. Ron'     },
  { username:'edith',    password:'edith',    role:'Manager', displayName:'Ms. Edith'   },
  { username:'allan',    password:'allan',    role:'Viewer',  displayName:'Mr. Allan'   },
  { username:'jastenet', password:'jastenet', role:'Manager', displayName:'Mr. Jastenet'}
];

function getUsers() { return USERS; }

function validateUser(u, p) {
  for (var i = 0; i < USERS.length; i++) {
    if (USERS[i].username === u && USERS[i].password === p) {
      return { username: USERS[i].username, role: USERS[i].role, displayName: USERS[i].displayName };
    }
  }
  return null;
}

var STATUS_MAP = {
  'Bid Opportunity':    ['Submitted For Approval','Rejected'],
  'Submitted':          ['Approved','Disapproved'],
  'Participating':      ['Processing Bid Docs','Submission & Opening of Bid Docs'],
  'Bid Result':         ['Pass','Fail'],
  'Post Qualification': ['Technical Pass','Technical Fail','Financial Pass','Financial Fail',
                          'NOA Received','NTP Received','PO Received'],
  'Consignment':        ['PO Received'],
  'RFQ':                ['Submitted for COGS','COGS Approved','COGS Disapproved','PO to be Received']
};

var HEADERS = [
  'ID','Date','Type','Status','Description','ITB No.','Agency/Institution','Region','Encoded By',
  'Pre-Bid ABC Non-VAT (₱)','COGS (₱)',
  'Ded 1 VAT (%)','Ded 2 EWT (₱)','Ded 3 Commitment Fee (%)','Ded 4 Direct Expenses (₱)',
  'Net (₱)','Total Deductions (₱)',
  'Approved','Rejected',
  'Attended Pre-Bid','Attendee','Bid Docs Bought By','Bid Docs Price','Bid Docs Reviewed By',
  'Assembling Started On','Assembling Completed On',
  'Submitted By','Submission Date & Time',
  'Noted By','Noted Date & Time',
  'PO Sent On','PO Sent By',
  'PO Entries (JSON)',
  'Deliveries (JSON)','Collections (JSON)',
  'Last Edited By',
  'Deadline',
  'Awarded ABC (₱)',
  'Comments',
  'NOA Received Date',
  'NTP Received Date',
  'PO Received Date',
  'NOA PDF Link',
  'NTP PDF Link',
  'PO Received PDF Link',
  'PO Supplier PDF Link'
];

// ── Sheet bootstrap ──────────────────────────────────────────
// FIX #6: Also check header cell content, not just column count
function getSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sh = ss.getSheetByName(SHEET_NAME);
  if (!sh) {
    sh = ss.insertSheet(SHEET_NAME);
  }
  var lastCol = sh.getLastColumn();
  var firstCell = sh.getLastRow() > 0 ? sh.getRange(1, 1).getValue() : '';
  if (lastCol < COL_COUNT || firstCell !== 'ID') {
    sh.getRange(1, 1, 1, COL_COUNT).setValues([HEADERS]);
    sh.getRange('1:1').setFontWeight('bold');
    sh.setFrozenRows(1);
    try { sh.autoResizeColumns(1, COL_COUNT); } catch(e) {}
  }
  return sh;
}

function getNotifSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sh = ss.getSheetByName(NOTIF_SHEET);
  if (!sh) {
    sh = ss.insertSheet(NOTIF_SHEET);
    sh.getRange(1,1,1,6).setValues([['ID','Timestamp','Type','Message','Read','Actor']]);
    sh.getRange('1:1').setFontWeight('bold');
  }
  return sh;
}

// ── Helpers ──────────────────────────────────────────────────
function num(v) { var n = Number(v); return isNaN(n) ? 0 : n; }

function calculateNet(r) {
  var abc = num(r.abc);
  return abc - num(r.cogs) - abc * num(r.ded1) / 100 - num(r.ded2) - abc * num(r.ded3) / 100 - num(r.ded4);
}

function calculateTotalDed(r) {
  var abc = num(r.abc);
  return abc * num(r.ded1) / 100 + num(r.ded2) + abc * num(r.ded3) / 100 + num(r.ded4);
}

function safeJSON(v) {
  if (!v || v === '') return [];
  try { return JSON.parse(v); } catch(e) { return []; }
}

function toJSON(v) {
  if (!v || (Array.isArray(v) && v.length === 0)) return '';
  try { return JSON.stringify(v); } catch(e) { return ''; }
}

function safeDate(v) {
  if (!v || v === '') return '';
  try {
    var d = new Date(v);
    if (isNaN(d.getTime())) return '';
    return d;
  } catch(e) { return ''; }
}

function str(v) { return v ? String(v) : ''; }

// ── Migration ────────────────────────────────────────────────
function migrateType(t) {
  if (t === 'Proposed') return 'Bid Opportunity';
  return t || 'Bid Opportunity';
}

function migrateStatus(s, t) {
  var mt = migrateType(t);
  if (mt === 'Bid Opportunity') {
    if (s === 'All' || s === 'Approved by Chairman' || s === 'Submitted to Chairman') return 'Submitted For Approval';
    if (s === 'Rejected by Chairman') return 'Rejected';
  }
  if (mt === 'Submitted') {
    if (s === 'BAC Under Evaluation' || s === 'BAC Awarded') return 'Approved';
    if (s === 'BAD Cancelled') return 'Disapproved';
  }
  if (!s) {
    var opts = STATUS_MAP[mt];
    return (opts && opts.length > 0) ? opts[0] : 'Submitted For Approval';
  }
  return s;
}

// ── Read all records ─────────────────────────────────────────
function getBidRecords() {
  try {
    var sh = getSheet();
    var data = sh.getDataRange().getValues();
    if (data.length <= 1) return [];
    var rows = [];
    for (var i = 1; i < data.length; i++) {
      var r = data[i];
      if (!r[0] || String(r[0]).trim() === '') continue;
      var rawType = str(r[2]) || 'Bid Opportunity';
      var type = migrateType(rawType);
      rows.push({
        id:          String(r[0]),
        date:        r[1] ? new Date(r[1]).toISOString() : new Date().toISOString(),
        type:        type,
        status:      migrateStatus(str(r[3]), rawType),
        description: str(r[4]),
        itbNo:       str(r[5]),
        agency:      str(r[6]),
        region:      str(r[7]),
        encodedBy:   str(r[8]),
        abc:         num(r[9]),
        cogs:        num(r[10]),
        ded1:        num(r[11]),
        ded2:        num(r[12]),
        ded3:        num(r[13]),
        ded4:        num(r[14]),
        net:         num(r[15]),
        totalDed:    num(r[16]),
        approved:    r[17] === true || r[17] === 'TRUE' || r[17] === 1,
        rejected:    r[18] === true || r[18] === 'TRUE' || r[18] === 1,
        preBidAttended:    str(r[19]),
        preBidAttendee:    str(r[20]),
        bidDocsBoughtBy:   str(r[21]),
        bidDocsPrice:      num(r[22]),
        bidDocsReviewedBy: str(r[23]),
        assemblingStarted:   r[24] ? new Date(r[24]).toISOString() : '',
        assemblingCompleted: r[25] ? new Date(r[25]).toISOString() : '',
        submittedBy: str(r[26]),
        submittedAt: r[27] ? new Date(r[27]).toISOString() : '',
        notedBy:     str(r[28]),
        notedAt:     r[29] ? new Date(r[29]).toISOString() : '',
        poSentOn:    r[30] ? new Date(r[30]).toISOString() : '',
        poSentBy:    str(r[31]),
        poEntries:   safeJSON(r[32]),
        deliveries:  safeJSON(r[33]),
        collections: safeJSON(r[34]),
        lastEditedBy: str(r[35]),
        deadline:     r[36] ? new Date(r[36]).toISOString() : '',
        awardedAbc:   num(r[37]),
        comments:     str(r[38]),
        noaDate:      r[39] ? new Date(r[39]).toISOString() : '',
        ntpDate:      r[40] ? new Date(r[40]).toISOString() : '',
        poReceivedDate: r[41] ? new Date(r[41]).toISOString() : '',
        noaPdfLink:        str(r[42]),
        ntpPdfLink:        str(r[43]),
        poReceivedPdfLink: str(r[44]),
        poSupplierPdfLink: str(r[45])
      });
    }
    rows.sort(function(a, b) { return new Date(b.date) - new Date(a.date); });
    return rows;
  } catch(e) {
    Logger.log('getBidRecords error: ' + e);
    return [];
  }
}

// ── Build sheet row ──────────────────────────────────────────
function _buildRow(r, net, totalDed) {
  return [
    str(r.id),
    safeDate(r.date) || new Date(),
    str(r.type) || 'Bid Opportunity',
    str(r.status) || 'Submitted For Approval',
    str(r.description),
    str(r.itbNo),
    str(r.agency),
    str(r.region),
    str(r.encodedBy),
    num(r.abc),
    num(r.cogs),
    num(r.ded1),
    num(r.ded2),
    num(r.ded3),
    num(r.ded4),
    net,
    totalDed,
    r.approved ? true : false,
    r.rejected ? true : false,
    str(r.preBidAttended),
    str(r.preBidAttendee),
    str(r.bidDocsBoughtBy),
    num(r.bidDocsPrice),
    str(r.bidDocsReviewedBy),
    safeDate(r.assemblingStarted) || '',
    safeDate(r.assemblingCompleted) || '',
    str(r.submittedBy),
    safeDate(r.submittedAt) || '',
    str(r.notedBy),
    safeDate(r.notedAt) || '',
    safeDate(r.poSentOn) || '',
    str(r.poSentBy),
    toJSON(r.poEntries || []),
    toJSON(r.deliveries || []),
    toJSON(r.collections || []),
    str(r.lastEditedBy),
    safeDate(r.deadline) || '',
    num(r.awardedAbc),
    str(r.comments),
    safeDate(r.noaDate) || '',
    safeDate(r.ntpDate) || '',
    safeDate(r.poReceivedDate) || '',
    str(r.noaPdfLink),
    str(r.ntpPdfLink),
    str(r.poReceivedPdfLink),
    str(r.poSupplierPdfLink)
  ];
}

// ── CRUD ─────────────────────────────────────────────────────
// FIX #3: Added SpreadsheetApp.flush() before returning records
function saveBidRecord(record) {
  try {
    var sh = getSheet();
    if (!record.id) {
      record.id = String(Date.now()) + Math.random().toString(36).substr(2, 5);
    }
    if (!record.date) record.date = new Date().toISOString();
    var net = calculateNet(record);
    var totalDed = calculateTotalDed(record);
    sh.insertRowAfter(1);
    sh.getRange(2, 1, 1, COL_COUNT).setValues([_buildRow(record, net, totalDed)]);
    SpreadsheetApp.flush(); // FIX: ensure write is committed before reading back
    _checkAlerts(record, net);
    return { success: true, id: record.id, records: getBidRecords() };
  } catch(e) {
    Logger.log('saveBidRecord error: ' + e);
    return { success: false, error: e.toString() };
  }
}

// FIX #4: Added SpreadsheetApp.flush() before returning records
function updateBidRecord(recordId, upd) {
  try {
    var sh = getSheet();
    var data = sh.getDataRange().getValues();
    var found = false;
    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) == String(recordId)) {
        upd.id = recordId;
        // Preserve PDF links if not provided in update
        if (!upd.noaPdfLink && data[i][42]) upd.noaPdfLink = str(data[i][42]);
        if (!upd.ntpPdfLink && data[i][43]) upd.ntpPdfLink = str(data[i][43]);
        if (!upd.poReceivedPdfLink && data[i][44]) upd.poReceivedPdfLink = str(data[i][44]);
        if (!upd.poSupplierPdfLink && data[i][45]) upd.poSupplierPdfLink = str(data[i][45]);
        var net = calculateNet(upd);
        var totalDed = calculateTotalDed(upd);
        sh.getRange(i + 1, 1, 1, COL_COUNT).setValues([_buildRow(upd, net, totalDed)]);
        SpreadsheetApp.flush(); // FIX: ensure write is committed before reading back
        found = true;
        break;
      }
    }
    if (!found) return { success: false, error: 'Record not found: ' + recordId };
    return { success: true, records: getBidRecords() };
  } catch(e) {
    Logger.log('updateBidRecord error: ' + e);
    return { success: false, error: e.toString() };
  }
}

function deleteBidRecord(recordId) {
  try {
    var sh = getSheet();
    var data = sh.getDataRange().getValues();
    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) == String(recordId)) {
        sh.deleteRow(i + 1);
        break;
      }
    }
    return { success: true, records: getBidRecords() };
  } catch(e) {
    Logger.log('deleteBidRecord error: ' + e);
    return { success: false, error: e.toString() };
  }
}

// FIX #5: Moved getBidRecords() outside the loop — was being called N times per bulk update
function bulkUpdateRecords(ids, changes) {
  try {
    var allRecs = getBidRecords(); // FIX: fetch once, not inside the loop
    for (var j = 0; j < ids.length; j++) {
      for (var k = 0; k < allRecs.length; k++) {
        if (allRecs[k].id === ids[j]) {
          var merged = {};
          for (var key in allRecs[k]) merged[key] = allRecs[k][key];
          for (var key2 in changes) merged[key2] = changes[key2];
          updateBidRecord(ids[j], merged);
          break;
        }
      }
    }
    return { success: true, records: getBidRecords() };
  } catch(e) { return { success: false, error: e.toString() }; }
}

function bulkDeleteRecords(ids) {
  try {
    for (var j = 0; j < ids.length; j++) {
      deleteBidRecord(ids[j]);
    }
    return { success: true, records: getBidRecords() };
  } catch(e) { return { success: false, error: e.toString() }; }
}

// ── Notifications ─────────────────────────────────────────────
function getNotifications() {
  try {
    var sh = getNotifSheet();
    var data = sh.getDataRange().getValues();
    if (data.length <= 1) return [];
    var result = [];
    for (var i = 1; i < data.length; i++) {
      result.push({
        id: String(data[i][0]),
        ts: data[i][1] ? new Date(data[i][1]).toISOString() : '',
        type: str(data[i][2]) || 'info',
        message: str(data[i][3]),
        read: data[i][4] === true || data[i][4] === 'TRUE',
        actor: str(data[i][5])
      });
    }
    result.reverse();
    return result;
  } catch(e) { return []; }
}

// FIX #1: Generate a proper unique ID and write it as the first column
function addNotification(type, message, actor) {
  try {
    var id = String(Date.now()) + Math.random().toString(36).substr(2, 4);
    getNotifSheet().appendRow([id, new Date(), type || 'info', message || '', false, actor || '']);
    return { success: true };
  } catch(e) { return { success: false }; }
}

function markNotificationsRead(ids) {
  try {
    var sh = getNotifSheet();
    var data = sh.getDataRange().getValues();
    for (var i = 1; i < data.length; i++) {
      if (!ids || ids.indexOf(String(data[i][0])) >= 0) {
        sh.getRange(i + 1, 5).setValue(true);
      }
    }
    return { success: true };
  } catch(e) { return { success: false }; }
}

function clearAllNotifications() {
  try {
    var sh = getNotifSheet();
    if (sh.getLastRow() > 1) sh.deleteRows(2, sh.getLastRow() - 1);
    return { success: true };
  } catch(e) { return { success: false }; }
}

// ── Alert engine ──────────────────────────────────────────────
function _checkAlerts(record, net, actor) {
  var n = (net !== undefined) ? net : calculateNet(record);
  var abc = num(record.abc);
  var mg = abc > 0 ? (n / abc) * 100 : 0;
  var by = actor ? ' [by ' + actor + ']' : '';
  if (mg < 5 && abc > 0)
    addNotification('warning', 'Low margin: "' + record.description + '" — only ' + mg.toFixed(1) + '% net margin' + by, actor || '');
  if (record.status === 'Fail')
    addNotification('danger', 'Failed: "' + record.description + '" (' + (record.agency || 'N/A') + ')' + by, actor || '');
  if (record.status === 'Disapproved')
    addNotification('info', 'Disapproved: "' + record.description + '"' + by, actor || '');
  if (record.status === 'NOA Received' || record.status === 'Approved')
    addNotification('info', 'Success: "' + record.description + '" — ' + record.status + by, actor || '');
  if (record.approved)
    addNotification('success', 'Approved: "' + record.description + '" — moved to Submitted' + by, actor || '');
  if (record.rejected)
    addNotification('danger', 'Rejected: "' + record.description + '" — returned to Bid Opportunity' + by, actor || '');
}

function runAlertScan() {
  var recs = getBidRecords();
  for (var i = 0; i < recs.length; i++) { _checkAlerts(recs[i]); }
  return { success: true, count: recs.length };
}

// ── Summary stats ─────────────────────────────────────────────
function getSummaryStats() {
  var recs = getBidRecords();
  var sub = 0, succ = 0, fail = 0, appv = 0, rej = 0;
  var tABC = 0, tCOGS = 0, tDed = 0, tNet = 0;
  for (var i = 0; i < recs.length; i++) {
    var r = recs[i];
    tABC += num(r.abc);
    tCOGS += num(r.cogs);
    tDed += calculateTotalDed(r);
    tNet += calculateNet(r);
    if (r.approved) appv++;
    if (r.rejected) rej++;
    if (r.type === 'Submitted') sub++;
    if (['NOA Received','NTP Received','PO Received','Approved'].indexOf(r.status) >= 0) succ++;
    if (r.status === 'Fail') fail++;
  }
  return {
    total: recs.length, totalABC: tABC, totalCOGS: tCOGS, totalDed: tDed, totalNet: tNet,
    approved: appv, rejected: rej, successful: succ, dq: fail,
    winRate: sub > 0 ? (succ / sub * 100).toFixed(1) : '0.0'
  };
}

// ── PDF Upload to Google Drive ─────────────────────────────────
function uploadPdfToDrive(base64Data, fileName, projectName, docType) {
  try {
    var parentFolder = DriveApp.getFolderById(DRIVE_FOLDER_ID);
    var safeName = (projectName || 'Unknown Project').replace(/[^a-zA-Z0-9 _\-().]/g, '').substring(0, 100).trim() || 'Unknown Project';
    var projFolder;
    var folders = parentFolder.getFoldersByName(safeName);
    if (folders.hasNext()) {
      projFolder = folders.next();
    } else {
      projFolder = parentFolder.createFolder(safeName);
    }
    var decoded = Utilities.base64Decode(base64Data);
    var blob = Utilities.newBlob(decoded, 'application/pdf', fileName);
    var file = projFolder.createFile(blob);
    file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
    return { success: true, url: file.getUrl(), fileName: file.getName() };
  } catch(e) {
    Logger.log('uploadPdfToDrive error: ' + e);
    return { success: false, error: e.toString() };
  }
}

// FIX #2: Added SpreadsheetApp.flush() after writing PDF URL to ensure consistency
function uploadAndLinkPdf(recordId, base64Data, fileName, projectName, docType) {
  try {
    var result = uploadPdfToDrive(base64Data, fileName, projectName, docType);
    if (!result.success) return result;
    var sh = getSheet();
    var data = sh.getDataRange().getValues();
    // Map docType to 1-based column index
    var colMap = { 'noa': 43, 'ntp': 44, 'po': 45, 'po_supplier': 46 };
    var colIndex = colMap[docType];
    if (!colIndex) return { success: false, error: 'Invalid docType: ' + docType };
    for (var i = 1; i < data.length; i++) {
      if (String(data[i][0]) == String(recordId)) {
        sh.getRange(i + 1, colIndex).setValue(result.url);
        SpreadsheetApp.flush(); // FIX: force write before reading records back
        break;
      }
    }
    addNotification('info', 'PDF uploaded: ' + docType.toUpperCase() + ' for "' + projectName + '"');
    return { success: true, url: result.url, records: getBidRecords() };
  } catch(e) {
    Logger.log('uploadAndLinkPdf error: ' + e);
    return { success: false, error: e.toString() };
  }
}

// ── Web app ───────────────────────────────────────────────────
function doGet() {
  return HtmlService.createHtmlOutputFromFile('index')
    .setTitle('BLS-BidTracker Pro')
    .addMetaTag('viewport', 'width=device-width,initial-scale=1')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}
