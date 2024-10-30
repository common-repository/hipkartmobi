/*
* Set plugin's page url into global JS variable "hipKartMobiPluginPageURL"
*/
var hipKartMobiPluginPageURL = Hip_ajax_object.hipKartMobiPluginPageURL;

/*
* @openSectionhipkart opens up the section clicked by user on plugin page.
*/
var hipKartMobileAppSection = 0;
function openSectionhipkart(cityName,HipcommerceKey,hipCommerceStoreID,TabClass) {
	jQuery('.tablinks').removeClass('active');
	jQuery('.'+TabClass).addClass('active');
	jQuery('.SectionLoader').show();
	jQuery('.tabcontent').hide();
	if(cityName == 'MobileApp'){
		GetMobileAppInfohipkart(HipcommerceKey,hipCommerceStoreID);
		setTimeout(function(){
			jQuery( ".SliderImage1" ).animate({'top':'-426px'},1000,function(){
				setTimeout(function(){
					jQuery( ".SliderImage1" ).animate({'top':'-836px'},1000,function(){
						setTimeout(function(){
							jQuery( ".SliderImage1" ).animate({'top':'0px'},'fast');
						},5000);
					});
				},3500);
			});
		},5000);
	}else{
		hipKartMobileAppSection = 0;
		jQuery('.SectionLoader').hide();
		jQuery('#'+cityName).show();
		jQuery('#'+cityName).show();
	}
	
	if(cityName == 'LinkCategory'){
		jQuery.ajax({
			type: 'POST',
			dataType:'json',
			url: Hip_ajax_object.ajax_url,
			data: {
				'action'    	:'GetHipCategories',
				'hipCommerceKey': HipcommerceKey
			},
			success:function(data) {
				var Option = '';
				jQuery.each( data, function( i, val ) {
					Option = Option+'<option value="'+val.id+'_'+val.name+'">'+val.name+'</option>';
				});
				jQuery('.HipkartCategories select#HipkartCategoriesDropBox').append(Option);
			},
			error: function(errorThrown){
				console.log(errorThrown);
			}
		});
	}
}

/*
* @GetSubCategorieshipkart send Ajax request to fetch sub categories of main category.
*/
function GetSubCategorieshipkart(objId,hipCommerceKey){
	objId = objId.split("_");
	objId = objId[0];
	var $el = jQuery('#HipkartSubCategoriesDropBox');
	$el.html(' ');
	jQuery('.HipkartSubCategories').css('display','none');
	jQuery('.HipkartSubCategories2').css('display','none');
	if(objId != 0){
		jQuery('.HipkartCategories .loading_spinner').show();
		jQuery.ajax({
			type: 'POST',
			dataType:'json',
			url: Hip_ajax_object.ajax_url,
			data: {
				'action'    	:'GetHipSubCategories',
				'hipCommerceKey':hipCommerceKey,
				'categoryid'	:objId
			},
			success:function(data) {
				console.log('subcat'+data.CODE);
				if(data.CODE == '100'){
					jQuery('.HipkartCategories .loading_spinner').hide();
					var NewOption = '<span class="HipkartMAinSpan2"><select id="HipkartSubCategoriesDropBox" onchange="GetThirdSubCategorieshipkart(this.value,\''+hipCommerceKey+'\');"><option value="0">Choose Hipkart Sub Category</option>';
					jQuery.each(data.RESPONSE, function( i, val ) {
						jQuery.each(val, function( ix, valx ) {
							NewOption = NewOption+'<option value="'+valx.category.id+'_'+valx.category.name+'">'+valx.category.name+'</option>';
						});
					});
					NewOption = NewOption+'</select><span class="loading_spinner" style="display:none;"></span></span>';
					jQuery('.HipkartSubCategories').show();
					jQuery('.HipkartSubCategories').html(NewOption);
				}else{
					jQuery('.HipkartCategories .loading_spinner').hide();
					jQuery('.HipkartSubCategories').html(' ');
					jQuery('.HipkartSubCategories2').html(' ');
				}
			},
			error: function(errorThrown){
				console.log(errorThrown);
			}
		});
	}
}

/*
* @GetThirdSubCategorieshipkart send Ajax request to fetch sub categories of sub category.
*/
function GetThirdSubCategorieshipkart(ObjID,hipCommerceKey){
	ObjID = ObjID.split("_");
	ObjID = ObjID[0];
	var $el = jQuery('#HipkartSubCategoriesDropBox2');
	$el.html(' ');
	jQuery('.HipkartSubCategories2').css('display','none');
	if(ObjID != 0){
		jQuery('.HipkartSubCategories .loading_spinner').show();
		jQuery.ajax({
			type: 'POST',
			dataType:'json',
			url: Hip_ajax_object.ajax_url,
			data: {
				'action'    	:'GetHipThirdSubCategories',
				'hipCommerceKey':hipCommerceKey,
				'categoryid'	:ObjID
			},
			success:function(data) {
				if(data.CODE == '100'){
					jQuery('.HipkartSubCategories .loading_spinner').hide();
					var ThirdLevelOption = '<span class="HipkartMAinSpan2"><select id="HipkartSubCategoriesDropBox2"><option value="0">Choose Hipkart Sub Category 2</option>';
					jQuery.each(data.RESPONSE, function( i, val ) {
						jQuery.each(val, function( ix, valx ) {
							ThirdLevelOption = ThirdLevelOption+'<option value="'+valx.category.id+'_'+valx.category.name+'">'+valx.category.name+'</option>';
						});
					});
					ThirdLevelOption = ThirdLevelOption+'</select></span>';
					jQuery('.HipkartSubCategories2').show();
					jQuery('.HipkartSubCategories2').html(ThirdLevelOption);
				}else{
					jQuery('.HipkartSubCategories .loading_spinner').hide();
					jQuery('.HipkartSubCategories2').html('');
				}
			},
			error: function(errorThrown){
				console.log(errorThrown);
			}
		});
	}
}


/*
* @SavehipkartCategories send Ajax request to save linking of Woocommerce categories and hipKart categories.
*/
function SavehipkartCategories(hipCommerceKey){
	var validate = 1;
	jQuery('.saveCategory .loadingBox').show();
	jQuery('.saveCategory .loading_spinner').show();	

	jQuery('.WOcommerceErrorClass').remove();
	var WocommerceProCat = jQuery('.ProductSelect').val();
	var HipkartMainCat = jQuery('#HipkartCategoriesDropBox').val();
	var HipkartSubCat = jQuery('#HipkartSubCategoriesDropBox').val();
	var HipkartSubCat2 = jQuery('#HipkartSubCategoriesDropBox2').val();
	if(WocommerceProCat == '' || WocommerceProCat == 0){
		jQuery('.WocommerceCategoryRight').append('<span class="WOcommerceErrorClass">Please select Woocommerce Category.</span>');
		validate = 0;
		
	}
	if(HipkartMainCat == '' || HipkartMainCat == 0){
		jQuery('.HipkartMainCategory .HipkartMainCategoryRight .HipkartCategories').append('<span class="WOcommerceErrorClass">Please select Hipkart Category.</span>');
		validate = 0;
		
	}
	if(HipkartSubCat == '' || HipkartSubCat == 0){
		jQuery('.HipkartMainCategory .HipkartMainCategoryRight .HipkartSubCategories').append('<span class="WOcommerceErrorClass">Please select Sub Hipkart Category.</span>');
		validate = 0;
	}
	if(HipkartSubCat2 == '' || HipkartSubCat2 == 0){
		jQuery('.HipkartMainCategory .HipkartMainCategoryRight .HipkartSubCategories2').append('<span class="WOcommerceErrorClass">Please select Sub2 Hipkart Category.</span>');
		validate = 0;
	}
	if(validate == 0){
		jQuery('.saveCategory .loading_spinner').hide();
		return false;
	}
	jQuery.ajax({
		type: 'POST',
		dataType:'json',
		url: Hip_ajax_object.ajax_url,
		data: {
			'action'    		:'SaveHipcommerceCategory',
			'hipCommerceKey'	:hipCommerceKey,
			'WocommerceProCat'	:WocommerceProCat,
			'HipkartMainCat'	:HipkartMainCat,
			'HipkartSubCat'		:HipkartSubCat,
			'HipkartSubCat2'	:HipkartSubCat2
		},
		success:function(data){
			console.log(data);
			if(data == 1){
				jQuery('.saveCategory .loadingBox').hide();
				jQuery('.saveCategory .loading_spinner').hide();	
				window.location.href = hipKartMobiPluginPageURL+'&section=category';
			}
		},
		error: function(errorThrown){
			console.log(errorThrown);
		}
	});
}

/*
* @DeleteHipCategory send Ajax request to unlink of Woocommerce category with hipKart category.
*/
function DeleteHipCategory(WocommerceCatID,RowID){
	var r = confirm("Are you sure you want to Unlink this ?");
	if(r){
		jQuery.ajax({
			type: 'POST',
			dataType:'json',
			url: Hip_ajax_object.ajax_url,
			data: {
				'action'    		:'DeleteHipcommerceCategory',
				'WocommerceCatID'	:WocommerceCatID
			},
			success:function(data){
				console.log(data);
				if(data == 1){
					jQuery('#WocommerceCAtS_'+RowID).remove();
				}
			},
			error: function(errorThrown){
				console.log(errorThrown);
			}
		});
	}
}

/*
* @LoginIntoHip send Ajax request to link store with hipKart store.
*/
function LoginIntoHip(){
	var Username = jQuery('.usernameLogin').val();
	var UserPassword = jQuery('.passwordClass').val();
	jQuery('.ErrorClass').remove();
	if(Username == ''){
		jQuery('.USERNAMEMAIN').prepend('<div class="ErrorClass">Please enter username.</div>');
		return false;
	}else if(UserPassword == ''){
		jQuery('.USERNAMEMAIN').prepend('<div class="ErrorClass">Please enter password.</div>');
		return false;
	}
	jQuery('.submitClasLodr').css('display','block');

	jQuery.ajax({
		type: 'POST',
		dataType:'json',
		url: Hip_ajax_object.ajax_url,
		data: {
			'action'    	:'HipStoreLoginData',
			'Username'		:Username,
			'UserPassword'	:UserPassword,
			'domain'		:Hip_ajax_object.Domain_url
		},
		success:function(data) {
			console.log(data);
			if(data.CODE == 101){
				jQuery('.USERNAMEMAIN').prepend('<div class="ErrorClass">Invalid username or password.</div>');
			}else if(data.CODE == 100){
				window.location.href = hipKartMobiPluginPageURL;
			}else if(data.CODE == 102){
				jQuery('.USERNAMEMAIN').prepend('<div class="ErrorClass" style="color: red;">Your store is not associated with Hipkart.</div>');
			}
		},
		error: function(errorThrown){
			console.log(errorThrown);
		}
	}); 
	
}

/*
* @BoostFormSubmitHipkart post product id to hipKart for Boost.
*/
function BoostFormSubmitHipkart(id){
	jQuery('#form_'+id).submit();
}

/*
* @unlinkhip send Ajax request to Unlink store with hipKart.
*/
function unlinkhip(){
	var r = confirm("Are you sure you want to Unlink this ?");
	if(r){
		jQuery.ajax({
			type: 'POST',
			url: Hip_ajax_object.ajax_url,
			data: {
				'action'    	:'UnlinkHipkartStore',
				'hipCommerceKey':LoggedInHipcommerceKey
			},
			success:function(data) {
				if(data == 1){
					location.reload();
				}
			},
			error: function(errorThrown){
				console.log(errorThrown);
			}
		});
	}
}

/*
* @GetMobileAppInfohipkart send Ajax request to get Mobile App information of the store.
*/
function GetMobileAppInfohipkart(HipcommerceKey,hipCommerceStoreID){
	hipKartMobileAppSection = 1;
	jQuery('.IOSAPPAVAIBLE1 .NoPAckage').remove();
	jQuery('.AndroidAPPAVAIBLE .NoPAckage').remove();
	jQuery.ajax({
		type: 'POST',
		url: Hip_ajax_object.ajax_url,
		data: {
			'action'    			:'GETHIPKARTAPPINFO',
			'HipcommerceKey'		:HipcommerceKey,
			'hipCommerceStoreID'	:hipCommerceStoreID
		},
		success:function(data) {
			if(hipKartMobileAppSection == 1){
				jQuery('.tabcontent').hide();
				if(data != 0){
					jQuery('#MobileApp').hide();
					jQuery('#HavingMobileApp').show();
					var APPResponse = JSON.parse(data);
					console.log(APPResponse);
					if(APPResponse != '' && APPResponse != 'null' && APPResponse != 'undefined'){
						var SelectedAppPack = APPResponse.RESPONSE.appInfo.app_package; // 2 is for android, 1 is for iOS
						var PaymentStatus = APPResponse.RESPONSE.appInfo.payment_status;
						var AndroidAppStatus = APPResponse.RESPONSE.appInfo.android_app_status;
						var AndroidAppSteps = APPResponse.RESPONSE.appInfo.android_app_step;
						var androidApp = APPResponse.RESPONSE.appInfo.android_app;
						var ios_app_step = APPResponse.RESPONSE.appInfo.ios_app_step;
						var iosApp = APPResponse.RESPONSE.appInfo.ios_app;
						var completeInfo = APPResponse.RESPONSE.appInfo.complete_info;
						
						if(completeInfo == 1){
							jQuery('.NotCompeletedYet').hide();
							if(SelectedAppPack == 2 && PaymentStatus == 1){
								jQuery('.AppStatusText.AndroidButton').show();
								jQuery('.AppStatusText.IOSButton').hide();
								jQuery('.IOSAPPTEXT').show();
								jQuery('.ANDROIDAPPTEXT').hide();
								jQuery('.progress_status_bar.APPLEProgress').show();
								jQuery('.progress_status_bar.ANDRIOD').hide();
								
								if(ios_app_step <= 10 && ios_app_step != 0){
									jQuery('.progress_status_bar.APPLEProgress').addClass('ActiveStep1');
								}else if(ios_app_step == 12){
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.APPLEProgress').addClass('ActiveStep2');
								}else if(ios_app_step == 13 && iosApp == 0){
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep2');
									jQuery('.progress_status_bar.APPLEProgress').addClass('ActiveStep3');
								}else if(ios_app_step >= 13 && iosApp == 1){
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep2');
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep3');
									jQuery('.progress_status_bar.APPLEProgress').addClass('ActiveStep4');
								}
								
							}else if(SelectedAppPack == 1 && PaymentStatus == 1){
								jQuery('.AppStatusText.AndroidButton').hide();
								jQuery('.AppStatusText.IOSButton').show();
								jQuery('.IOSAPPTEXT').hide();
								
								jQuery('.progress_status_bar.APPLEProgress').hide();
								jQuery('.progress_status_bar.ANDRIOD').show();
								
								if(AndroidAppSteps <= 5 && AndroidAppSteps != 0 ){
									jQuery('.progress_status_bar.ANDRIOD').addClass('ActiveStep1');
								}else if(AndroidAppSteps > 5 && AndroidAppSteps < 10){
									
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.ANDRIOD').addClass('ActiveStep2');
								}else if(AndroidAppSteps == 10 && androidApp == 0){
								
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep2');
									jQuery('.progress_status_bar.ANDRIOD').addClass('ActiveStep3');
								}else if(AndroidAppSteps >= 10 && androidApp == 1){
									
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep2');
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep3');
									jQuery('.progress_status_bar.ANDRIOD').addClass('ActiveStep4');
								}
							}else if(SelectedAppPack == 3 && PaymentStatus == 1){
								jQuery('.AppStatusText.AndroidButton').hide();
								jQuery('.AppStatusText.IOSButton').show();
								jQuery('.IOSAPPTEXT').show();
								jQuery('.ANDROIDAPPTEXT').show();
								jQuery('.AppStatusText.IOSButton').hide();
								jQuery('.AppStatusText.AndroidButton').hide();
								jQuery('.progress_status_bar.APPLEProgress').show();
								jQuery('.progress_status_bar.ANDRIOD').show();
								
								if(AndroidAppStatus <= 5 && AndroidAppSteps != 0 ){
									jQuery('.progress_status_bar.ANDRIOD').addClass('ActiveStep1');
								}else if(AndroidAppStatus > 5 && AndroidAppStatus < 10){
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.ANDRIOD').addClass('ActiveStep2');
								}else if(AndroidAppStatus == 10 && androidApp == 0){
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep2');
									jQuery('.progress_status_bar.ANDRIOD').addClass('ActiveStep3');
								}else if(AndroidAppStatus >= 10 && androidApp == 1){
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep2');
									jQuery('.progress_status_bar.ANDRIOD').removeClass('ActiveStep3');
									jQuery('.progress_status_bar.ANDRIOD').addClass('ActiveStep4');
								}
								
								
								//************************** IOS  *******************************//
								
								if(ios_app_step <= 10 && ios_app_step != 0){
									jQuery('.progress_status_bar.APPLEProgress').addClass('ActiveStep1');
								}
								if(ios_app_step == 12){
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.APPLEProgress').addClass('ActiveStep2');
								}
								if(ios_app_step == 13 && iosApp == 0){
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep2');
									jQuery('.progress_status_bar.APPLEProgress').addClass('ActiveStep3');
								}
								if(ios_app_step >= 13 && iosApp == 1){
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep1');
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep2');
									jQuery('.progress_status_bar.APPLEProgress').removeClass('ActiveStep3');
									jQuery('.progress_status_bar.APPLEProgress').addClass('ActiveStep4');
								}
								
							}else{
								jQuery('.IOSAPPAVAIBLE1').append('<div class="AppStatusText NoPAckage">Your app is under Preview Mode, you can make customizations <a class="Hipcolor" href="https://www.hipkart.mobi/" target="_blank">here</a>, to preview your app download our <a href="https://itunes.apple.com/us/app/hipkart/id1053387179" target="_blank" class="Hipcolor"> hipKart iOS App</a> and <a href="https://www.hipkart.mobi/preview-your-app/" target="_blank" class="Hipcolor">follow these steps.</a></div>');
								jQuery('.AndroidAPPAVAIBLE').append('<div class="AppStatusText NoPAckage">Your app is under Preview Mode, you can make customizations <a class="Hipcolor" href="https://www.hipkart.mobi/" target="_blank">here</a>, to preview your app download our <a href="https://play.google.com/store/apps/details?id=com.hipkart" target="_blank" class="Hipcolor">hipKart Android App</a> and <a href="https://www.hipkart.mobi/preview-your-app/" target="_blank" class="Hipcolor">follow these steps.</a></div>');
								jQuery('.AppStatusText.IOSButton').hide();
								jQuery('.AppStatusText.AndroidButton').hide();
							}
						}else{
							jQuery('.AndriodStatus').hide();
							jQuery('.NotCompeletedYet').show();
							
						}
					}
				}else{
					jQuery('#HavingMobileApp').hide();
					jQuery('#MobileApp').show();
					animateSlidesforMobileApphipkart();
				}
			
				jQuery('.SectionLoader').hide();
			}	
		},
		error: function(errorThrown){
			jQuery('.tabcontent').hide();
			jQuery('#HavingMobileApp').hide();
			jQuery('#MobileApp').show();
			jQuery('.SectionLoader').hide();
			animateSlidesforMobileApphipkart();
		}
		
	});
	
}

/*
* @animateSlidesforMobileApphipkart shows App into a Phone.
*/
function animateSlidesforMobileApphipkart(){
	setTimeout(function(){
		jQuery( ".SliderImage1" ).animate({'top':'-426px'},1000,function(){
			setTimeout(function(){
				jQuery( ".SliderImage1" ).animate({'top':'-836px'},1000,function(){
					setTimeout(function(){
						jQuery( ".SliderImage1" ).animate({'top':'0px'},'fast');
					},5000);
				});
			},3500);
		});
	},5000);
}

/*
* @getApphipkart Take user to get Mobile App for Store.
*/
function getApphipkart(id){
	jQuery('#Mobiform_'+id).submit();
}

/*
* @SaveSettingshipkart send Ajax Request to save user's settings.
*/
function SaveSettingshipkart(){
	var SettingsVal = [];
	jQuery.each(jQuery(".messageCheckbox:checked"), function(){            
		SettingsVal.push(jQuery(this).val());
	});
	jQuery('.SaveSettingButton .loadingBox').show();
	jQuery('.SaveSettingButton .loading_spinner').show();	

	jQuery.ajax({
		type: 'POST',
		dataType:'json',
		url: Hip_ajax_object.ajax_url,
		data: {
			'action'    	:'SaveHipkartSettings',
			'SettingsVal'	:SettingsVal,
			'domain'		:Hip_ajax_object.Domain_url // Make this dynamic
		},
		success:function(data) {
			jQuery('.SaveSettingButton .loadingBox').hide();
			jQuery('.SaveSettingButton .loading_spinner').hide();
		},
		error: function(errorThrown){
			console.log(errorThrown);
		}
	}); 
}