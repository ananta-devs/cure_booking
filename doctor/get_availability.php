<?php

class AvailabilityManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all availability for a specific doctor
     * @param int $doctor_id
     * @return array
     */
    public function getDoctorAvailability($doctor_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT dca.id, dca.doctor_id, dca.clinic_id, dca.availability_schedule, 
                       dca.created_at, c.clinic_name, c.clinic_address, c.clinic_phone,
                       d.doc_name, d.doc_email
                FROM doctor_clinic_assignments dca
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id
                LEFT JOIN doctor d ON dca.doctor_id = d.doc_id
                WHERE dca.doctor_id = ?
                ORDER BY c.clinic_name
            ");
            
            $stmt->execute([$doctor_id]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $availability = [];
            foreach ($assignments as $assignment) {
                $schedule = $this->parseSchedule($assignment['availability_schedule']);
                
                $availability[] = [
                    'assignment_id' => $assignment['id'],
                    'doctor_id' => $assignment['doctor_id'],
                    'doctor_name' => $assignment['doc_name'],
                    'doctor_email' => $assignment['doc_email'],
                    'clinic_id' => $assignment['clinic_id'],
                    'clinic_name' => $assignment['clinic_name'],
                    'clinic_address' => $assignment['clinic_address'],
                    'clinic_phone' => $assignment['clinic_phone'],
                    'schedule' => $schedule,
                    'available_slots' => $this->getAvailableSlots($schedule),
                    'created_at' => $assignment['created_at']
                ];
            }
            
            return $availability;
            
        } catch (PDOException $e) {
            error_log("Database error in getDoctorAvailability: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get availability for a specific clinic
     * @param int $clinic_id
     * @return array
     */
    public function getClinicAvailability($clinic_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT dca.id, dca.doctor_id, dca.clinic_id, dca.availability_schedule, 
                       dca.created_at, c.clinic_name, c.clinic_address, c.clinic_phone,
                       d.doc_name, d.doc_email, d.doc_specialization
                FROM doctor_clinic_assignments dca
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id
                LEFT JOIN doctor d ON dca.doctor_id = d.doc_id
                WHERE dca.clinic_id = ?
                ORDER BY d.doc_name
            ");
            
            $stmt->execute([$clinic_id]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $availability = [];
            foreach ($assignments as $assignment) {
                $schedule = $this->parseSchedule($assignment['availability_schedule']);
                
                $availability[] = [
                    'assignment_id' => $assignment['id'],
                    'doctor_id' => $assignment['doctor_id'],
                    'doctor_name' => $assignment['doc_name'],
                    'doctor_specialization' => $assignment['doc_specialization'],
                    'doctor_email' => $assignment['doc_email'],
                    'clinic_id' => $assignment['clinic_id'],
                    'clinic_name' => $assignment['clinic_name'],
                    'clinic_address' => $assignment['clinic_address'],
                    'clinic_phone' => $assignment['clinic_phone'],
                    'schedule' => $schedule,
                    'available_slots' => $this->getAvailableSlots($schedule),
                    'created_at' => $assignment['created_at']
                ];
            }
            
            return $availability;
            
        } catch (PDOException $e) {
            error_log("Database error in getClinicAvailability: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get availability for a specific doctor at a specific clinic
     * @param int $doctor_id
     * @param int $clinic_id
     * @return array|null
     */
    public function getDoctorClinicAvailability($doctor_id, $clinic_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT dca.id, dca.doctor_id, dca.clinic_id, dca.availability_schedule, 
                       dca.created_at, c.clinic_name, c.clinic_address, c.clinic_phone,
                       d.doc_name, d.doc_email, d.doc_specialization
                FROM doctor_clinic_assignments dca
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id
                LEFT JOIN doctor d ON dca.doctor_id = d.doc_id
                WHERE dca.doctor_id = ? AND dca.clinic_id = ?
            ");
            
            $stmt->execute([$doctor_id, $clinic_id]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assignment) {
                return null;
            }
            
            $schedule = $this->parseSchedule($assignment['availability_schedule']);
            
            return [
                'assignment_id' => $assignment['id'],
                'doctor_id' => $assignment['doctor_id'],
                'doctor_name' => $assignment['doc_name'],
                'doctor_specialization' => $assignment['doc_specialization'],
                'doctor_email' => $assignment['doc_email'],
                'clinic_id' => $assignment['clinic_id'],
                'clinic_name' => $assignment['clinic_name'],
                'clinic_address' => $assignment['clinic_address'],
                'clinic_phone' => $assignment['clinic_phone'],
                'schedule' => $schedule,
                'available_slots' => $this->getAvailableSlots($schedule),
                'created_at' => $assignment['created_at']
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getDoctorClinicAvailability: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get availability for a specific day of the week
     * @param string $day (monday, tuesday, etc.)
     * @param int|null $doctor_id Optional: filter by doctor
     * @param int|null $clinic_id Optional: filter by clinic
     * @return array
     */
    public function getAvailabilityByDay($day, $doctor_id = null, $clinic_id = null) {
        try {
            $query = "
                SELECT dca.id, dca.doctor_id, dca.clinic_id, dca.availability_schedule, 
                       dca.created_at, c.clinic_name, c.clinic_address, c.clinic_phone,
                       d.doc_name, d.doc_email, d.doc_specialization
                FROM doctor_clinic_assignments dca
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id
                LEFT JOIN doctor d ON dca.doctor_id = d.doc_id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($doctor_id) {
                $query .= " AND dca.doctor_id = ?";
                $params[] = $doctor_id;
            }
            
            if ($clinic_id) {
                $query .= " AND dca.clinic_id = ?";
                $params[] = $clinic_id;
            }
            
            $query .= " ORDER BY d.doc_name, c.clinic_name";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $availability = [];
            foreach ($assignments as $assignment) {
                $schedule = $this->parseSchedule($assignment['availability_schedule']);
                
                // Check if doctor is available on the specified day
                if (isset($schedule[$day])) {
                    $daySchedule = $schedule[$day];
                    $availableSlots = [];
                    
                    foreach ($daySchedule as $timeSlot => $isAvailable) {
                        if ($isAvailable) {
                            $availableSlots[] = $timeSlot;
                        }
                    }
                    
                    if (!empty($availableSlots)) {
                        $availability[] = [
                            'assignment_id' => $assignment['id'],
                            'doctor_id' => $assignment['doctor_id'],
                            'doctor_name' => $assignment['doc_name'],
                            'doctor_specialization' => $assignment['doc_specialization'],
                            'doctor_email' => $assignment['doc_email'],
                            'clinic_id' => $assignment['clinic_id'],
                            'clinic_name' => $assignment['clinic_name'],
                            'clinic_address' => $assignment['clinic_address'],
                            'clinic_phone' => $assignment['clinic_phone'],
                            'day' => $day,
                            'available_slots' => $availableSlots,
                            'created_at' => $assignment['created_at']
                        ];
                    }
                }
            }
            
            return $availability;
            
        } catch (PDOException $e) {
            error_log("Database error in getAvailabilityByDay: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all assignments with full details
     * @return array
     */
    public function getAllAvailability() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT dca.id, dca.doctor_id, dca.clinic_id, dca.availability_schedule, 
                       dca.created_at, c.clinic_name, c.clinic_address, c.clinic_phone,
                       d.doc_name, d.doc_email, d.doc_specialization
                FROM doctor_clinic_assignments dca
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id
                LEFT JOIN doctor d ON dca.doctor_id = d.doc_id
                ORDER BY d.doc_name, c.clinic_name
            ");
            
            $stmt->execute();
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $availability = [];
            foreach ($assignments as $assignment) {
                $schedule = $this->parseSchedule($assignment['availability_schedule']);
                
                $availability[] = [
                    'assignment_id' => $assignment['id'],
                    'doctor_id' => $assignment['doctor_id'],
                    'doctor_name' => $assignment['doc_name'],
                    'doctor_specialization' => $assignment['doc_specialization'],
                    'doctor_email' => $assignment['doc_email'],
                    'clinic_id' => $assignment['clinic_id'],
                    'clinic_name' => $assignment['clinic_name'],
                    'clinic_address' => $assignment['clinic_address'],
                    'clinic_phone' => $assignment['clinic_phone'],
                    'schedule' => $schedule,
                    'available_slots' => $this->getAvailableSlots($schedule),
                    'created_at' => $assignment['created_at']
                ];
            }
            
            return $availability;
            
        } catch (PDOException $e) {
            error_log("Database error in getAllAvailability: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Parse JSON schedule string
     * @param string $scheduleJson
     * @return array
     */
    private function parseSchedule($scheduleJson) {
        if (empty($scheduleJson)) {
            return [];
        }
        
        $decoded = json_decode($scheduleJson, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        
        return [];
    }
    
    /**
     * Extract available time slots from schedule
     * @param array $schedule
     * @return array
     */
    private function getAvailableSlots($schedule) {
        $availableSlots = [];
        
        foreach ($schedule as $day => $timeSlots) {
            $availableSlots[$day] = [];
            foreach ($timeSlots as $timeSlot => $isAvailable) {
                if ($isAvailable) {
                    $availableSlots[$day][] = $timeSlot;
                }
            }
        }
        
        return $availableSlots;
    }
    
    /**
     * Check if doctor is available at specific time
     * @param int $doctor_id
     * @param int $clinic_id
     * @param string $day
     * @param string $timeSlot
     * @return bool
     */
    public function isAvailable($doctor_id, $clinic_id, $day, $timeSlot) {
        $availability = $this->getDoctorClinicAvailability($doctor_id, $clinic_id);
        
        if (!$availability) {
            return false;
        }
        
        $schedule = $availability['schedule'];
        
        return isset($schedule[$day][$timeSlot]) && $schedule[$day][$timeSlot] === true;
    }
}

// Usage examples:
/*
// Initialize the class
$availabilityManager = new AvailabilityManager($pdo);

// Get all availability for doctor ID 3
$doctorAvailability = $availabilityManager->getDoctorAvailability(3);

// Get all availability for clinic ID 1
$clinicAvailability = $availabilityManager->getClinicAvailability(1);

// Get availability for doctor 3 at clinic 1
$specificAvailability = $availabilityManager->getDoctorClinicAvailability(3, 1);

// Get all doctors available on Monday
$mondayAvailability = $availabilityManager->getAvailabilityByDay('monday');

// Get doctors available on Tuesday at clinic 1
$tuesdayClinicAvailability = $availabilityManager->getAvailabilityByDay('tuesday', null, 1);

// Check if doctor 3 is available at clinic 1 on Monday 11:00-13:00
$isAvailable = $availabilityManager->isAvailable(3, 1, 'monday', '11:00-13:00');

// Get all availability data
$allAvailability = $availabilityManager->getAllAvailability();
*/

?>