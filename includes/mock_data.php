<?php
declare(strict_types=1);
// Fallback mock data for development/testing (Farman)

$currentUser = [
    'id'            => 1,
    'name'          => 'Sarah Mitchell',
    'email'         => 'sarah.mitchell@labauto.local',
    'role'          => 'administrator',
    'department_id' => 1,
];

$_SESSION['user'] = $currentUser;

// --- Departments -----------------------------------------------------------
$mockDepartments = [
    ['id' => 1, 'name' => 'HV Lab',          'description' => 'High-voltage testing & certification',  'status' => 'active', 'tester_count' => 8,  'active_tests' => 12, 'avg_cycle' => '3.2 days'],
    ['id' => 2, 'name' => 'Insulation Lab',   'description' => 'Insulation resistance & dielectric',    'status' => 'active', 'tester_count' => 5,  'active_tests' => 7,  'avg_cycle' => '2.8 days'],
    ['id' => 3, 'name' => 'Mechanical Lab',   'description' => 'Enclosure integrity & IP rating',       'status' => 'active', 'tester_count' => 6,  'active_tests' => 9,  'avg_cycle' => '4.1 days'],
    ['id' => 4, 'name' => 'Environmental Lab','description' => 'Temperature, humidity & UV exposure',    'status' => 'active', 'tester_count' => 4,  'active_tests' => 3,  'avg_cycle' => '5.6 days'],
    ['id' => 5, 'name' => 'EMC Lab',          'description' => 'Electromagnetic compatibility testing',  'status' => 'inactive','tester_count' => 0,  'active_tests' => 0,  'avg_cycle' => '—'],
];

// --- Users / Testers -------------------------------------------------------
$mockUsers = [
    ['id' => 1, 'name' => 'Sarah Mitchell',   'email' => 'sarah.mitchell@labauto.local',  'role' => 'administrator',    'department' => '—',              'status' => 'active', 'last_login' => '2026-08-07 09:14:00'],
    ['id' => 2, 'name' => 'R. Chowdhury',     'email' => 'r.chowdhury@labauto.local',     'role' => 'testing_engineer', 'department' => 'HV Lab',         'status' => 'active', 'last_login' => '2026-08-07 08:45:00'],
    ['id' => 3, 'name' => 'A. Fernandes',      'email' => 'a.fernandes@labauto.local',     'role' => 'lab_technician',   'department' => 'HV Lab',         'status' => 'active', 'last_login' => '2026-08-06 16:30:00'],
    ['id' => 4, 'name' => 'J. Rao',            'email' => 'j.rao@labauto.local',           'role' => 'quality_manager',  'department' => 'Insulation Lab', 'status' => 'active', 'last_login' => '2026-08-07 07:22:00'],
    ['id' => 5, 'name' => 'S. Verma',          'email' => 's.verma@labauto.local',         'role' => 'auditor',          'department' => '—',              'status' => 'active', 'last_login' => '2026-08-05 11:00:00'],
    ['id' => 6, 'name' => 'P. Nakamura',       'email' => 'p.nakamura@labauto.local',      'role' => 'lab_technician',   'department' => 'Insulation Lab', 'status' => 'active', 'last_login' => '2026-08-07 08:10:00'],
    ['id' => 7, 'name' => 'K. Okonkwo',        'email' => 'k.okonkwo@labauto.local',       'role' => 'testing_engineer', 'department' => 'Mechanical Lab', 'status' => 'active', 'last_login' => '2026-08-06 14:55:00'],
    ['id' => 8, 'name' => 'L. Garcia',         'email' => 'l.garcia@labauto.local',        'role' => 'lab_technician',   'department' => 'Mechanical Lab', 'status' => 'inactive','last_login' => '2026-07-20 09:00:00'],
];

// --- Testing Types ---------------------------------------------------------
$mockTestingTypes = [
    ['id' => 1, 'name' => 'Insulation Resistance Test',  'category' => 'Electrical',  'version' => 3, 'status' => 'active',   'params' => 4, 'records' => 48, 'created_by' => 'R. Chowdhury', 'updated_at' => '2026-08-01'],
    ['id' => 2, 'name' => 'Dielectric Strength Test',    'category' => 'Electrical',  'version' => 2, 'status' => 'active',   'params' => 3, 'records' => 35, 'created_by' => 'R. Chowdhury', 'updated_at' => '2026-07-28'],
    ['id' => 3, 'name' => 'Mechanical Endurance',        'category' => 'Mechanical',  'version' => 1, 'status' => 'active',   'params' => 5, 'records' => 22, 'created_by' => 'K. Okonkwo',   'updated_at' => '2026-07-15'],
    ['id' => 4, 'name' => 'IP Rating Assessment',        'category' => 'Mechanical',  'version' => 1, 'status' => 'active',   'params' => 3, 'records' => 18, 'created_by' => 'K. Okonkwo',   'updated_at' => '2026-07-10'],
    ['id' => 5, 'name' => 'Thermal Cycling',             'category' => 'Environmental','version' => 2, 'status' => 'active',  'params' => 6, 'records' => 14, 'created_by' => 'Sarah Mitchell','updated_at' => '2026-06-22'],
    ['id' => 6, 'name' => 'UV Resistance (Legacy)',       'category' => 'Environmental','version' => 1, 'status' => 'archived','params' => 3, 'records' => 9,  'created_by' => 'Sarah Mitchell','updated_at' => '2026-03-01'],
];

// --- Products --------------------------------------------------------------
$mockProducts = [
    ['id' => 1,  'serial' => 'FZ-2026-0451', 'model' => 'Transformer T-400kV',    'department' => 'HV Lab',          'status' => 'in_testing',            'attempts' => 1, 'registered_by' => 'R. Chowdhury', 'created_at' => '2026-08-05 10:30:00'],
    ['id' => 2,  'serial' => 'FZ-2026-0452', 'model' => 'Switchgear SG-12',       'department' => 'HV Lab',          'status' => 'submitted_for_review',  'attempts' => 1, 'registered_by' => 'R. Chowdhury', 'created_at' => '2026-08-04 14:15:00'],
    ['id' => 3,  'serial' => 'FZ-2026-0453', 'model' => 'Cable Termination CT-72','department' => 'Insulation Lab',  'status' => 'pending_cpri_approval', 'attempts' => 1, 'registered_by' => 'R. Chowdhury', 'created_at' => '2026-08-03 09:00:00'],
    ['id' => 4,  'serial' => 'FZ-2026-0454', 'model' => 'Bushing B-245',          'department' => 'HV Lab',          'status' => 'approved',              'attempts' => 1, 'registered_by' => 'R. Chowdhury', 'created_at' => '2026-08-01 11:20:00'],
    ['id' => 5,  'serial' => 'FZ-2026-0455', 'model' => 'Insulator INS-33',       'department' => 'Insulation Lab',  'status' => 'registered',            'attempts' => 0, 'registered_by' => 'K. Okonkwo',   'created_at' => '2026-08-06 16:45:00'],
    ['id' => 6,  'serial' => 'FZ-2026-0456', 'model' => 'Enclosure ENC-IP65',     'department' => 'Mechanical Lab',  'status' => 'in_testing',            'attempts' => 1, 'registered_by' => 'K. Okonkwo',   'created_at' => '2026-08-02 08:30:00'],
    ['id' => 7,  'serial' => 'FZ-2026-0457', 'model' => 'Surge Arrester SA-10',   'department' => 'HV Lab',          'status' => 'failed_pending_rework', 'attempts' => 2, 'registered_by' => 'R. Chowdhury', 'created_at' => '2026-07-29 13:10:00'],
    ['id' => 8,  'serial' => 'FZ-2026-0458', 'model' => 'Transformer T-220kV',    'department' => 'HV Lab',          'status' => 'released',              'attempts' => 1, 'registered_by' => 'R. Chowdhury', 'created_at' => '2026-07-25 09:45:00'],
    ['id' => 9,  'serial' => 'FZ-2026-0459', 'model' => 'Cable Joint CJ-66',      'department' => 'Insulation Lab',  'status' => 'on_hold',               'attempts' => 1, 'registered_by' => 'K. Okonkwo',   'created_at' => '2026-07-22 10:00:00'],
    ['id' => 10, 'serial' => 'FZ-2026-0460', 'model' => 'Panel Board PB-LV',      'department' => 'Mechanical Lab',  'status' => 'assigned',              'attempts' => 0, 'registered_by' => 'K. Okonkwo',   'created_at' => '2026-08-07 07:00:00'],
    ['id' => 11, 'serial' => 'FZ-2026-0461', 'model' => 'CT Metering Unit',       'department' => 'HV Lab',          'status' => 'in_testing',            'attempts' => 1, 'registered_by' => 'R. Chowdhury', 'created_at' => '2026-08-06 11:30:00'],
    ['id' => 12, 'serial' => 'FZ-2026-0462', 'model' => 'VCB Breaker 36kV',       'department' => 'HV Lab',          'status' => 'registered',            'attempts' => 0, 'registered_by' => 'R. Chowdhury', 'created_at' => '2026-08-07 08:15:00'],
];

// --- Testing Records -------------------------------------------------------
$mockTestingRecords = [
    ['id' => 1,  'product_serial' => 'FZ-2026-0451', 'product_model' => 'Transformer T-400kV',  'testing_type' => 'Insulation Resistance Test', 'department' => 'HV Lab',         'tester' => 'A. Fernandes', 'status' => 'in_testing',           'attempt' => 1, 'due_date' => '2026-08-10', 'created_at' => '2026-08-05 10:45:00'],
    ['id' => 2,  'product_serial' => 'FZ-2026-0451', 'product_model' => 'Transformer T-400kV',  'testing_type' => 'Dielectric Strength Test',   'department' => 'HV Lab',         'tester' => 'A. Fernandes', 'status' => 'assigned',             'attempt' => 1, 'due_date' => '2026-08-12', 'created_at' => '2026-08-05 10:45:00'],
    ['id' => 3,  'product_serial' => 'FZ-2026-0452', 'product_model' => 'Switchgear SG-12',     'testing_type' => 'Insulation Resistance Test', 'department' => 'HV Lab',         'tester' => 'A. Fernandes', 'status' => 'submitted_for_review', 'attempt' => 1, 'due_date' => '2026-08-08', 'created_at' => '2026-08-04 14:30:00'],
    ['id' => 4,  'product_serial' => 'FZ-2026-0453', 'product_model' => 'Cable Termination CT-72','testing_type' => 'Dielectric Strength Test', 'department' => 'Insulation Lab', 'tester' => 'P. Nakamura',  'status' => 'passed',               'attempt' => 1, 'due_date' => '2026-08-06', 'created_at' => '2026-08-03 09:15:00'],
    ['id' => 5,  'product_serial' => 'FZ-2026-0456', 'product_model' => 'Enclosure ENC-IP65',   'testing_type' => 'Mechanical Endurance',       'department' => 'Mechanical Lab', 'tester' => 'K. Okonkwo',   'status' => 'in_testing',           'attempt' => 1, 'due_date' => '2026-08-09', 'created_at' => '2026-08-02 08:45:00'],
    ['id' => 6,  'product_serial' => 'FZ-2026-0456', 'product_model' => 'Enclosure ENC-IP65',   'testing_type' => 'IP Rating Assessment',       'department' => 'Mechanical Lab', 'tester' => 'K. Okonkwo',   'status' => 'assigned',             'attempt' => 1, 'due_date' => '2026-08-11', 'created_at' => '2026-08-02 08:45:00'],
    ['id' => 7,  'product_serial' => 'FZ-2026-0457', 'product_model' => 'Surge Arrester SA-10', 'testing_type' => 'Insulation Resistance Test', 'department' => 'HV Lab',         'tester' => 'A. Fernandes', 'status' => 'failed',               'attempt' => 2, 'due_date' => '2026-08-01', 'created_at' => '2026-07-30 09:00:00'],
    ['id' => 8,  'product_serial' => 'FZ-2026-0454', 'product_model' => 'Bushing B-245',        'testing_type' => 'Dielectric Strength Test',   'department' => 'HV Lab',         'tester' => 'A. Fernandes', 'status' => 'passed',               'attempt' => 1, 'due_date' => '2026-08-03', 'created_at' => '2026-08-01 11:30:00'],
    ['id' => 9,  'product_serial' => 'FZ-2026-0460', 'product_model' => 'Panel Board PB-LV',    'testing_type' => 'Mechanical Endurance',       'department' => 'Mechanical Lab', 'tester' => 'K. Okonkwo',   'status' => 'assigned',             'attempt' => 1, 'due_date' => '2026-08-14', 'created_at' => '2026-08-07 07:15:00'],
    ['id' => 10, 'product_serial' => 'FZ-2026-0461', 'product_model' => 'CT Metering Unit',     'testing_type' => 'Insulation Resistance Test', 'department' => 'HV Lab',         'tester' => 'P. Nakamura',  'status' => 'in_testing',           'attempt' => 1, 'due_date' => '2026-08-13', 'created_at' => '2026-08-06 11:45:00'],
];

// --- Activity Feed ---------------------------------------------------------
// Timestamps are generated dynamically relative to "now" so they always feel fresh
$_now = new DateTimeImmutable();
$mockActivity = [
    ['actor' => 'A. Fernandes',  'action' => 'submitted measurements for',     'target' => 'Switchgear SG-12',       'target_id' => 'TR-003', 'time' => $_now->modify('-12 minutes')->format('Y-m-d H:i:s'),  'url' => 'testing/detail.php?id=3'],
    ['actor' => 'R. Chowdhury',  'action' => 'assigned testing to',            'target' => 'Panel Board PB-LV',      'target_id' => 'TR-009', 'time' => $_now->modify('-28 minutes')->format('Y-m-d H:i:s'),  'url' => 'testing/detail.php?id=9'],
    ['actor' => 'J. Rao',        'action' => 'approved CPRI for',              'target' => 'Bushing B-245',          'target_id' => 'PR-004', 'time' => $_now->modify('-1 hour')->format('Y-m-d H:i:s'),      'url' => 'products/detail.php?id=4'],
    ['actor' => 'P. Nakamura',   'action' => 'started testing',                'target' => 'CT Metering Unit',       'target_id' => 'TR-010', 'time' => $_now->modify('-2 hours')->format('Y-m-d H:i:s'),     'url' => 'testing/detail.php?id=10'],
    ['actor' => 'K. Okonkwo',    'action' => 'registered product',             'target' => 'VCB Breaker 36kV',       'target_id' => 'PR-012', 'time' => $_now->modify('-3 hours')->format('Y-m-d H:i:s'),     'url' => 'products/detail.php?id=12'],
    ['actor' => 'Sarah Mitchell','action' => 'updated testing type',            'target' => 'Insulation Resistance',  'target_id' => 'TT-001', 'time' => $_now->modify('-5 hours')->format('Y-m-d H:i:s'),     'url' => 'admin/testing-types.php'],
    ['actor' => 'A. Fernandes',  'action' => 'recorded fail for',              'target' => 'Surge Arrester SA-10',   'target_id' => 'TR-007', 'time' => $_now->modify('-26 hours')->format('Y-m-d H:i:s'),    'url' => 'testing/detail.php?id=7'],
    ['actor' => 'R. Chowdhury',  'action' => 'reviewed and passed',            'target' => 'Cable Termination CT-72','target_id' => 'TR-004', 'time' => $_now->modify('-28 hours')->format('Y-m-d H:i:s'),    'url' => 'testing/detail.php?id=4'],
];

// --- Notifications ---------------------------------------------------------
$mockNotifications = [
    ['id' => 1, 'title' => 'Test submitted for review',       'message' => 'A. Fernandes submitted measurements for Switchgear SG-12',   'type' => 'warning',    'time' => $_now->modify('-12 minutes')->format('Y-m-d H:i:s'),  'read' => false, 'url' => 'testing/detail.php?id=3'],
    ['id' => 2, 'title' => 'CPRI approval needed',            'message' => 'Cable Termination CT-72 is pending your CPRI approval',       'type' => 'info',       'time' => $_now->modify('-1 hour')->format('Y-m-d H:i:s'),      'read' => false, 'url' => 'products/detail.php?id=3'],
    ['id' => 3, 'title' => 'Test failed — rework required',   'message' => 'Surge Arrester SA-10 failed insulation resistance test',       'type' => 'error',      'time' => $_now->modify('-26 hours')->format('Y-m-d H:i:s'),    'read' => false, 'url' => 'testing/detail.php?id=7'],
    ['id' => 4, 'title' => 'New product registered',          'message' => 'VCB Breaker 36kV has been registered by K. Okonkwo',           'type' => 'success',    'time' => $_now->modify('-3 hours')->format('Y-m-d H:i:s'),     'read' => true,  'url' => 'products/detail.php?id=12'],
    ['id' => 5, 'title' => 'Testing type updated',            'message' => 'Insulation Resistance Test updated to version 3',              'type' => 'info',       'time' => $_now->modify('-5 hours')->format('Y-m-d H:i:s'),     'read' => true,  'url' => 'admin/testing-types.php'],
];
