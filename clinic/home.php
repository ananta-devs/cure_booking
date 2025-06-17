<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>HealthCare Clinic Dashboard</title>
        <link
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
            rel="stylesheet"
        />
        <link rel="stylesheet" href="styles.css" />
    </head>
    <body>
        <?php
            include './top-header.php';
        ?>

        <div class="container">
            <?php
                include './sidebar.php';
            ?>
            <!-- Main Content -->
            <main class="main-content">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="content-section active">
                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">127</div>
                            <div class="stat-label">Total Patients Today</div>
                            <div class="stat-trend trend-up">
                                ↗ +12% from yesterday
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">8</div>
                            <div class="stat-label">Active Doctors</div>
                            <div class="stat-trend trend-up">
                                ↗ All doctors available
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">34</div>
                            <div class="stat-label">Lab Tests Pending</div>
                            <div class="stat-trend trend-down">
                                ↘ -5 from morning
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">$12,450</div>
                            <div class="stat-label">Today's Revenue</div>
                            <div class="stat-trend trend-up">
                                ↗ +8% from average
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="activity-section">
                        <h2 class="section-title">Recent Activity</h2>
                        <div class="activity-item">
                            <div
                                class="activity-icon"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #e6fffa,
                                        #b2f5ea
                                    );
                                    color: #319795;
                                "
                            >
                                👨‍⚕️
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    Dr. Johnson completed consultation with
                                    Patient #1247
                                </div>
                                <div class="activity-time">2 minutes ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div
                                class="activity-icon"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #fef5e7,
                                        #fad089
                                    );
                                    color: #d69e2e;
                                "
                            >
                                🧪
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    Lab results ready for Patient #1245
                                </div>
                                <div class="activity-time">15 minutes ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div
                                class="activity-icon"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #f0fff4,
                                        #c6f6d5
                                    );
                                    color: #38a169;
                                "
                            >
                                📅
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    New appointment scheduled with Dr. Williams
                                </div>
                                <div class="activity-time">32 minutes ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div
                                class="activity-icon"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #fed7d7,
                                        #feb2b2
                                    );
                                    color: #e53e3e;
                                "
                            >
                                🚨
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    Emergency patient admitted to Room 205
                                </div>
                                <div class="activity-time">1 hour ago</div>
                            </div>
                        </div>
                    </div>

                    <!-- Extra content to demonstrate scrolling -->
                    <div class="activity-section" style="margin-top: 2rem">
                        <h2 class="section-title">Additional Information</h2>
                        <div style="padding: 1rem 0">
                            <p style="margin-bottom: 1rem; color: #4a5568">
                                This section demonstrates the scrollable main
                                content area. The sidebar remains fixed while
                                you can scroll through this content.
                            </p>
                            <div
                                style="
                                    height: 200px;
                                    background: linear-gradient(
                                        135deg,
                                        #f7fafc,
                                        #edf2f7
                                    );
                                    border-radius: 12px;
                                    margin: 1rem 0;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: #718096;
                                "
                            >
                                <p>Sample content block</p>
                            </div>
                            <div
                                style="
                                    height: 200px;
                                    background: linear-gradient(
                                        135deg,
                                        #e6fffa,
                                        #b2f5ea
                                    );
                                    border-radius: 12px;
                                    margin: 1rem 0;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: #319795;
                                "
                            >
                                <p>Another content block</p>
                            </div>
                            <div
                                style="
                                    height: 200px;
                                    background: linear-gradient(
                                        135deg,
                                        #fef5e7,
                                        #fad089
                                    );
                                    border-radius: 12px;
                                    margin: 1rem 0;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: #d69e2e;
                                "
                            >
                                <p>More scrollable content</p>
                            </div>
                            <div
                                style="
                                    height: 200px;
                                    background: linear-gradient(
                                        135deg,
                                        #f0fff4,
                                        #c6f6d5
                                    );
                                    border-radius: 12px;
                                    margin: 1rem 0;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: #38a169;
                                "
                            >
                                <p>Final content block</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>


    </body>
</html>
