jQuery(document).ready(function($) {
    // Shared locations data
    const locationData = {
        'andhra_pradesh': ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Tirupati'],
        'arunachal_pradesh': ['Itanagar', 'Tawang', 'Pasighat', 'Ziro', 'Bomdila'],
        'assam': ['Guwahati', 'Dibrugarh', 'Silchar', 'Tezpur', 'Jorhat'],
        'bihar': ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Darbhanga'],
        'chhattisgarh': ['Raipur', 'Bhilai', 'Bilaspur', 'Korba', 'Durg'],
        'goa': ['Panaji', 'Margao', 'Vasco da Gama', 'Mapusa', 'Ponda'],
        'gujarat': ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar'],
        'haryana': ['Faridabad', 'Gurgaon', 'Panipat', 'Ambala', 'Hisar'],
        'himachal_pradesh': ['Shimla', 'Manali', 'Dharamshala', 'Solan', 'Mandi'],
        'jharkhand': ['Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro', 'Deoghar'],
        'karnataka': ['Bengaluru', 'Mysuru', 'Mangaluru', 'Hubli', 'Belagavi'],
        'kerala': ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Thrissur', 'Kollam'],
        'madhya_pradesh': ['Bhopal', 'Indore', 'Jabalpur', 'Gwalior', 'Ujjain'],
        'maharashtra': ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Aurangabad'],
        'manipur': ['Imphal', 'Bishnupur', 'Thoubal', 'Ukhrul', 'Churachandpur'],
        'meghalaya': ['Shillong', 'Tura', 'Jowai', 'Baghmara', 'Nongpoh'],
        'mizoram': ['Aizawl', 'Lunglei', 'Serchhip', 'Champhai', 'Kolasib'],
        'nagaland': ['Kohima', 'Dimapur', 'Mokokchung', 'Tuensang', 'Zunheboto'],
        'odisha': ['Bhubaneswar', 'Cuttack', 'Rourkela', 'Puri', 'Sambalpur'],
        'punjab': ['Amritsar', 'Ludhiana', 'Jalandhar', 'Patiala', 'Bathinda'],
        'rajasthan': ['Jaipur', 'Jodhpur', 'Udaipur', 'Kota', 'Ajmer'],
        'sikkim': ['Gangtok', 'Namchi', 'Gyalshing', 'Mangan', 'Ravangla'],
        'tamil_nadu': ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem'],
        'telangana': ['Hyderabad', 'Warangal', 'Nizamabad', 'Karimnagar', 'Khammam'],
        'tripura': ['Agartala', 'Udaipur', 'Dharmanagar', 'Kailasahar', 'Ambassa'],
        'uttar_pradesh': ['Lucknow', 'Kanpur', 'Varanasi', 'Agra', 'Noida'],
        'uttarakhand': ['Dehradun', 'Haridwar', 'Rishikesh', 'Roorkee', 'Haldwani'],
        'west_bengal': ['Kolkata', 'Howrah', 'Asansol', 'Durgapur', 'Siliguri'],
        'andaman_nicobar': ['Port Blair', 'Diglipur', 'Mayabunder', 'Rangat', 'Havelock Island'],
        'chandigarh': ['Chandigarh'],
        'dadra_nagar_haveli_daman_diu': ['Silvassa', 'Daman', 'Diu'],
        'delhi': ['New Delhi', 'Dwarka', 'Rohini', 'Saket', 'Karol Bagh'],
        'jammu_kashmir': ['Srinagar', 'Jammu', 'Anantnag', 'Baramulla', 'Udhampur'],
        'ladakh': ['Leh', 'Kargil', 'Nubra', 'Diskit', 'Padum'],
        'lakshadweep': ['Kavaratti', 'Agatti', 'Amini', 'Andrott', 'Kalpeni'],
        'puducherry': ['Puducherry', 'Karaikal', 'Mahe', 'Yanam']
    };

    // ==========================================================
    // JETSMARTFILTERS LOGIC
    // ==========================================================
    function initSmartFilters() {
        const $regionFilter = $('#regionfilter select.jet-select__control');
        const $cityFilter = $('#cityfilter select.jet-select__control');

        if ($regionFilter.length && $cityFilter.length) {
            if ($regionFilter.data('custom-smartfilters-initialized')) return;

            const currentRegionValueByJSF = $regionFilter.val();
            const currentCityValueByJSF = $cityFilter.val();

            $regionFilter.empty().append('<option value="">Select Region</option>');
            $.each(Object.keys(locationData), function(i, regionKey) {
                const regionLabel = regionKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                $regionFilter.append($('<option>', { value: regionLabel, text: regionLabel }));
            });

            if (currentRegionValueByJSF) {
                let regionToTrySelect = currentRegionValueByJSF;
                if (locationData.hasOwnProperty(currentRegionValueByJSF)) { // If JSF stored the key
                    regionToTrySelect = currentRegionValueByJSF.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                }
                if ($regionFilter.find('option[value="' + regionToTrySelect.replace(/"/g, '\\"') + '"]').length) {
                    $regionFilter.val(regionToTrySelect);
                }
            }

            $regionFilter.off('change.customLocationJSF').on('change.customLocationJSF', function() {
                const selectedRegionLabel = $(this).val();
                $cityFilter.empty().append('<option value="">Select City</option>');
                if (selectedRegionLabel) {
                    const regionKey = Object.keys(locationData).find(key =>
                        key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) === selectedRegionLabel
                    );
                    if (regionKey && locationData[regionKey]) {
                        $.each(locationData[regionKey], function(i, city) {
                            $cityFilter.append($('<option>', { value: city, text: city }));
                        });
                    }
                }
                if (typeof jQuery.fn.niceSelect === 'function' && $cityFilter.parent().hasClass('nice-select-wrapper')) {
                    $cityFilter.niceSelect('update');
                } else {
                    $cityFilter.trigger('change'); // Notify JetSmartFilters
                }
            });

            $regionFilter.triggerHandler('change.customLocationJSF'); // Populate cities based on current region

            if (currentCityValueByJSF) {
                 // City values are now labels. currentCityValueByJSF might be a label or a slug.
                if ($cityFilter.find('option[value="' + currentCityValueByJSF.replace(/"/g, '\\"') + '"]').length) {
                    $cityFilter.val(currentCityValueByJSF);
                } else { // Fallback: if JSF stored something else (e.g. slug), try matching by text
                    const matchingOptionByText = $cityFilter.find('option').filter((idx, opt) => $(opt).text() === currentCityValueByJSF);
                    if (matchingOptionByText.length) {
                        $cityFilter.val(matchingOptionByText.val()); // Value is already the label
                    }
                }
                if (typeof jQuery.fn.niceSelect === 'function' && $cityFilter.parent().hasClass('nice-select-wrapper')) {
                    $cityFilter.niceSelect('update');
                } else {
                     $cityFilter.trigger('change'); // Notify JetSmartFilters
                }
            }
             if (typeof jQuery.fn.niceSelect === 'function' && $regionFilter.parent().hasClass('nice-select-wrapper')) {
                $regionFilter.niceSelect('update');
            }

            $regionFilter.data('custom-smartfilters-initialized', true);
        }
    }

    // ========================================================
    // JETFORMBUILDER LOGIC
    // ========================================================
    function initFormBuilderFields() {
        const $forms = $('form.jet-form-builder');
        if (!$forms.length) return;

        $forms.each(function() {
            const $currentForm = $(this);
            // Ensure you target the select elements correctly.
            // If .regionfilter or .cityfilter are classes on the select elements themselves:
            const $formRegion = $currentForm.find('select.regionfilter');
            const $formCity = $currentForm.find('select.cityfilter');
            // If they are wrappers, you might need: $currentForm.find('.regionfilter select');

            if ($formRegion.length && $formCity.length) {
                if ($formRegion.data('custom-jfb-initialized')) return;

                const defaultRegionValFromForm = $formRegion.val();
                const defaultCityValFromForm = $formCity.val();

                $formRegion.empty().append('<option value="">Select State</option>');
                $.each(Object.keys(locationData), function(i, regionKey) {
                    const regionLabel = regionKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    $formRegion.append($('<option>', { value: regionLabel, text: regionLabel }));
                });

                $formRegion.off('change.customLocationJFB').on('change.customLocationJFB', function() {
                    const selectedRegionLabel = $(this).val();
                    $formCity.empty().append('<option value="">Select City</option>');
                    if (selectedRegionLabel) {
                        const regionKey = Object.keys(locationData).find(key =>
                            key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) === selectedRegionLabel
                        );
                        if (regionKey && locationData[regionKey]) {
                            $.each(locationData[regionKey], function(i, city) {
                                $formCity.append($('<option>', { value: city, text: city }));
                            });
                        }
                    }
                    if (typeof jQuery.fn.niceSelect === 'function' && $formCity.parent().hasClass('nice-select-wrapper')) {
                        $formCity.niceSelect('update');
                    }
                    $formCity.trigger('change'); // For JFB conditional logic or other listeners
                });

                if (defaultRegionValFromForm) {
                    let regionToSet = defaultRegionValFromForm;
                    if (locationData.hasOwnProperty(defaultRegionValFromForm)) { // If form stored the key
                        regionToSet = defaultRegionValFromForm.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }
                    if ($formRegion.find('option[value="' + regionToSet.replace(/"/g, '\\"') + '"]').length) {
                        $formRegion.val(regionToSet);
                    }
                }

                $formRegion.triggerHandler('change.customLocationJFB'); // Populate cities

                if (defaultCityValFromForm) {
                    if ($formCity.find('option[value="' + defaultCityValFromForm.replace(/"/g, '\\"') + '"]').length) {
                        $formCity.val(defaultCityValFromForm);
                         if (typeof jQuery.fn.niceSelect === 'function' && $formCity.parent().hasClass('nice-select-wrapper')) {
                             $formCity.niceSelect('update');
                         }
                        $formCity.trigger('change'); // For JFB conditional logic
                    }
                }
                
                if (typeof jQuery.fn.niceSelect === 'function' && $formRegion.parent().hasClass('nice-select-wrapper')) {
                   $formRegion.niceSelect('update');
                }

                $formRegion.data('custom-jfb-initialized', true);
            }
        });
    }

    // ======================================================
    // INITIALIZATION
    // ======================================================
    function initializeAll() {
        initSmartFilters();
        initFormBuilderFields();
    }

    initializeAll(); // Run on initial page load

    // Re-run after JetFormBuilder loads or reloads a form
    $(document).on('jet-form-builder/after-form-load', function() {
        setTimeout(initializeAll, 100); // Timeout to ensure form elements are ready
    });
    
    // Re-run after any AJAX request completes to catch JetSmartFilters updates
    // Be cautious: this can run frequently. Specific JSF events are better if available.
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Check if the AJAX request is likely from JetSmartFilters
        // You might need to inspect 'settings.url' or 'settings.data' for more specific targeting
        if (settings && settings.url && settings.url.includes('jetsmartfilters')) {
             setTimeout(initializeAll, 100); // Timeout to ensure filter elements are ready
        }
    });
});