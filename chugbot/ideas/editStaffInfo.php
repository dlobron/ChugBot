<?php
    session_start();
    include_once 'addEdit.php';
    include_once 'formItem.php';
    bounceToLogin();

    $editStaffInfoPage = new EditPage("Edit Staff Info", "Please update your information as needed",
                                      "admin_data", "admin_data_id");
    $editStaffInfoPage->addColumn("admin_email");
    $editStaffInfoPage->addColumn("admin_email_username", FALSE);
    $editStaffInfoPage->addColumn("admin_email_password", FALSE);
    $editStaffInfoPage->setConstantIdValue(1);
    
    $editStaffInfoPage->handlePost();
    
    $emailField = new FormItemSingleTextField("Admin Email Address",
                                              TRUE, "admin_email", 0);
    $emailField->setInputType("text");
    $emailField->setInputClass("element text medium");
    $emailField->setInputMaxLength(255);
    $emailField->setInputValue($editStaffInfoPage->columnValue("admin_email"));
    $emailField->setGuideText("Enter or update the administrative email address.");
    $editStaffInfoPage->addFormItem($emailField);
    
    $emailUserNameField = new FormItemSingleTextField("Admin Email Username",
                                                      FALSE, "admin_email_username", 1);
    $emailUserNameField->setInputType("text");
    $emailUserNameField->setInputClass("element text medium");
    $emailUserNameField->setInputMaxLength(255);
    $emailUserNameField->setInputValue($editStaffInfoPage->columnValue("admin_email_username"));
    $emailUserNameField->setGuideText("Enter the username for the admin email account (if you are not sure of this, leave it blank to default to the admin email address).");
    $editStaffInfoPage->addFormItem($emailUserNameField);
    
    $emailPasswordField = new FormItemSingleTextField("Admin Email Account Password",
                                                      FALSE, "admin_email_password", 1);
    $emailPasswordField->setInputType("text");
    $emailPasswordField->setInputClass("element text medium");
    $emailPasswordField->setInputMaxLength(255);
    $emailPasswordField->setInputValue($editStaffInfoPage->columnValue("admin_email_password"));
    $emailPasswordField->setGuideText("Enter the admin email account password.  This password is different from the main admin password: it is only used to send email.  If you are not sure of this value, please contact the site administrator.  NOTE: This value is not stored securely, so please do not use a valuable password.");
    $editStaffInfoPage->addFormItem($emailPasswordField);
    
    $editStaffInfoPage->renderForm();
    
    ?>
    