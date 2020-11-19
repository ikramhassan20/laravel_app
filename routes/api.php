<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['api.auth:api', 'api.version', 'api']], function () {
    Route::get('user', 'Api\AppUserController@getUser');
    /*************Gallery Routes******************************/
    Route::get('gallery', 'Api\GalleryController@index')->name('gallery.index');
    Route::post('gallery', 'Api\GalleryController@store')->name('gallery.store');
    Route::delete('gallery/{id}', 'Api\GalleryController@destroy')->name('gallery.destroy');

    /*************Gallery Routes******************************/

    /****Lookup Routes ***/
    Route::get('lookup', 'Api\Company\LookupController@index')->name('lookup.index');
    Route::post('lookup', 'Api\Company\LookupController@store')->name('lookup.store');
    Route::get('lookup/filters', 'Api\Company\Lookup\LookupFiltersController@index')->name('lookup.filters');
    Route::put('lookup/{lookup}', 'Api\Company\LookupController@update')->name('lookup.update');
    Route::delete('lookup/{lookup}', 'Api\Company\LookupController@destroy')->name('lookup.destroy');
    Route::get('lookup/edit/{lookup}', 'Api\Company\LookupController@show')->name('lookup.show');
    /****Lookup Routes ***/
    /****Language Routes ***/
    /****Company Routes ***/
    Route::get('company', 'Api\CompanyController@index')->name('company.index');
    Route::post('companyStatus', 'Api\CompanyController@statusUpdate')->name('company.companyStatus');
    Route::get('company/edit/{id}', 'Api\CompanyController@edit')->name('company.edit');
    Route::put('company/{id}', 'Api\CompanyController@update')->name('company.update');
    Route::put('company/updatePassword/{id}', 'Api\CompanyController@updatePassword')->name('company.updatePassword');
    Route::get('company/users/listing', 'Api\CompanyController@user')->name('company.users');
    Route::get('company/users/unsubscribe-listing', 'Api\CompanyController@unsubscribeUser');
    Route::get('user/stats/{id}', 'Api\CompanyController@userStats');
    Route::post('user/stats/notification', 'Api\CompanyController@userStatsChangeNotification');
    Route::post('company/rebuild/cache', 'Api\CompanyController@rebuildCache')->name('company.rebuildCache');
    Route::get('companies', 'Api\CompanyController@getAll')->name('company.companies');
    Route::delete('company/{id}', 'Api\CompanyController@destroy')->name('company.destroy');
    Route::post('company/users/export', 'Api\CompanyController@exportUsers')->name('company.export.users');
    Route::get('company/users/exports/file', 'Api\CompanyController@getExportUsersCsv');
    Route::get('company/package-details/{companyId}', 'Api\CompanyController@getPackageDetails');
    Route::get('company/side-filters', 'Api\CompanyController@getCompanySideFilters');
    Route::get('company/package-listing/{companyId}', 'Api\CompanyController@getPackageListing');
    Route::post('company/change-package', 'Api\CompanyController@changePackage');

    /* bounced users */
    Route::get('bounced/users', 'Api\CompanyController@bouncedUsers')->name('bounced.users');
    Route::delete('bounced/user/delete/{id}', 'Api\CompanyController@bouncedUserDelete')->name('bounced.userDelete');

    Route::get('commands', 'Api\CommandController@commandsCache')->name('company.commandsCache');
    Route::get('command/data', 'Api\CommandController@commandData')->name('company.commandData');

    Route::put('userStatus/{id}', 'Api\CompanyController@userStatus')->name('company.userStatus');
    Route::delete('company/users/delete/{id}', 'Api\CompanyController@userDelete')->name('company.userDelete');
    /****Company Routes ***/
    /****NewsFeed Routes ***/
    Route::get('newsFeed', 'Api\NewsFeedController@index')->name('newsFeed.index');
    /****NewsFeed Routes ***/
    /****Location Routes ***/
    Route::get('location', 'Api\LocationController@index')->name('location.index');
    Route::put('location/{id}', 'Api\LocationController@update')->name('location.update');
    Route::post('location', 'Api\LocationController@store')->name('location.store');
    Route::delete('location/delete/{Location}', 'Api\LocationController@destroy')->name('location.destroy');
    Route::get('location/{id}', 'Api\LocationController@editlocation')->name('location.editlocation');
    Route::post('location/area', 'Api\LocationController@areaStore')->name('location.area');
    Route::post('location/area/delete', 'Api\LocationController@areaDelete')->name('location.areaDelete');
    /****Location Routes ***/

    Route::get('language', 'Api\Language\LanguageController@index')->name('language.index');
    Route::post('language', 'Api\Language\LanguageController@store')->name('language.store');
    Route::put('language/{lang}', 'Api\Language\LanguageController@update')->name('language.update');
    Route::delete('language/{lang}', 'Api\Language\LanguageController@destroy')->name('language.destroy');
    Route::get('language/{lang}', 'Api\Language\LanguageController@destroy')->name('language.destroy');
    Route::get('language/current/{lang}', 'Api\Language\LanguageController@show')->name('language.show');
    /****Language Routes ***/

    /*
     * Attributes Routes
     * */
    Route::get('attribute', 'Api\Attribute\AttributeController@index')->name('attribute.index');
    Route::post('attribute', 'Api\Attribute\AttributeController@store')->name('attribute.store');
    Route::put('attribute/{id}', 'Api\Attribute\AttributeController@update')->name('attribute.update');
    Route::delete('attribute/{id}', 'Api\Attribute\AttributeController@destroy')->name('attribute.destroy');
    Route::get('attribute/current/{id}', 'Api\Attribute\AttributeController@show')->name('attribute.show');
    Route::get('attribute/value/{code}', 'Api\Attribute\AttributeController@getValues')->name('attribute.value');
    Route::post('attribute/value/take-action', 'Api\Attribute\AttributeController@takeAction')->name('attribute.action');

    Route::get('company/presets/segment', 'Api\Company\PresetsController@getSegmentPreSetsFilters');
    Route::get('company/presets/segmentList', 'Api\Company\PresetsController@getSegmentsList');
    Route::get('company/presets/campaign', 'Api\Company\PresetsController@getCampaignPreSets');
    Route::get('company/presets/newsfeed', 'Api\Company\PresetsController@getNewsFeedPreSets');
    Route::get('company/presets/languages/{searching}', 'Api\Company\PresetsController@getLanguagesBySearching');
    Route::get('company/presets/segments/{searching}', 'Api\Company\PresetsController@getSegmentsBySearching');
    Route::get('company/presets/users/{searching}/{campaignType}/{deviceType}', 'Api\Company\PresetsController@getUsersBySearching');
    Route::get('company/presets/attribute/listing', 'Api\Company\PresetsController@getAttributes');
    Route::resource('groups', 'Api\AppGroupsController');

//    Route::resource('groups', 'Api\AppGroupsController');
    Route::get('groups', 'Api\AppGroupsController@index')->name('groups.index');
    Route::post('groups', 'Api\AppGroupsController@store')->name('groups.store');
    Route::get('groups/{group}', 'Api\AppGroupsController@show')->name('groups.show');
    Route::put('groups/{group}', 'Api\AppGroupsController@update')->name('groups.update');
    Route::delete('groups/{group}', 'Api\AppGroupsController@destroy')->name('groups.destroy');
    Route::put('groups/current/{group}', 'Api\AppGroup\SetCurrentAppGroupController@update')->name('groups.current');
    Route::get('app', 'Api\AppGroupsController@applist')->name('app.applist');
    Route::post('app', 'Api\AppGroupsController@saveApp')->name('app.saveApp');
    Route::delete('app/{appId}', 'Api\AppGroupsController@destroyApp')->name('app.destroyApp');
    Route::get('app/{appId}', 'Api\AppGroupsController@editApp')->name('app.editApp');
    Route::put('app/current/{app}', 'Api\AppGroupsController@appUpdate')->name('app.appUpdate');
    Route::post('appStatus', 'Api\AppGroupsController@statusUpdate')->name('app.appStatus');

    //    Route::resource('campaigns', 'Api\CampaignsController');
    /*
    * Campaign Routes
    * */
    Route::get('campaigns', 'Api\CampaignsController@index')->name('campaigns.index');
    Route::post('campaigns', 'Api\CampaignsController@store')->name('campaigns.store');
    Route::get('campaigns/{campaignId}', 'Api\CampaignsController@show')->name('campaigns.show');
    Route::put('campaigns/{campaign}', 'Api\CampaignsController@update')->name('campaigns.update');
    Route::delete('campaigns/{campaign}', 'Api\CampaignsController@destroy')->name('campaigns.destroy');
    Route::get('campaign/filters', 'Api\CampaignsController@getFilters')->name('campaigns.filters');
    Route::get('campaign/export/{campaignId}', 'Api\CampaignsController@getExportUsers')->name('campaigns.export');
    Route::get('campaign/capping-settings', 'Api\CampaignsController@getCappingSettings')->name('campaigns.capping');
    Route::post('campaign/capping-save', 'Api\CampaignsController@saveCappingSettings')->name('campaigns.capping.save');
    Route::get('campaign/queues', 'Api\CampaignsController@getCampaignQueueListing')->name('campaigns.queue');

    /*
   * semantic board
  */
    Route::get('boards', 'Api\SemanticBoardController@index')->name('boards.index');
    Route::post('board', 'Api\SemanticBoardController@store');
    Route::get('boards/{boardId}', 'Api\SemanticBoardController@show')->name('boards.show');
    Route::put('board/{board}', 'Api\SemanticBoardController@update')->name('board.update');
    Route::get('board/filters', 'Api\SemanticBoardController@getFilters')->name('boards.filters');
    Route::get('board/export/{boardId}', 'Api\SemanticBoardController@getExportUsers')->name('board.export');

    /*
     * semantic board stats
    */
    Route::get('board/tracking/stats/{id}', 'Api\SemanticBoardStatsController@trackingStats')->name('board.boardTrackingStats');
    Route::get('board/tracking/list/{id}', 'Api\SemanticBoardStatsController@boardTracking')->name('board.boardTracking');
    Route::get('board/tracking/export/{boardId}', 'Api\SemanticBoardStatsController@boardTrackingExport')->name('board.boardTracking');
    Route::get('board/variants/{boardId}', 'Api\SemanticBoardStatsController@getBoardVariants');
    Route::get('board/stats/chart/{boardId}', 'Api\SemanticBoardStatsController@boardViewsClicksChart');
    Route::get('board/stats/{id}', 'Api\SemanticBoardStatsController@boardStats');
    Route::get('board/stats/views-clicks/{id}', 'Api\SemanticBoardStatsController@boardViewsClicksCount');

    Route::get('board/stats/countries-chart/{id}', 'Api\SemanticBoardStatsController@boardCountriesChart');
    Route::get('board/stats/activity-chart/{id}', 'Api\SemanticBoardStatsController@boardActivityChart');
    Route::get('board/stats/link-activity-stats/{id}', 'Api\SemanticBoardStatsController@boardLinkActivityStats');
    Route::get('board/stats/board-activity-stats-export/{id}', 'Api\SemanticBoardStatsController@boardActivityStatsExport');

    Route::post('board/resend/notification', 'Api\SemanticBoardStatsController@resendNotification')->name('board.resendNotification');

    Route::get('board/user/stats/steps/{boardId}', 'Api\SemanticBoardStatsController@boardUserStatsSteps')->name('board.user.stats.steps');
    Route::get('board/user/stats/{boardId}', 'Api\SemanticBoardStatsController@boardUserStats')->name('board.user.stats');
    // board queues api
    Route::get('board/queues', 'Api\SemanticBoardController@getBoardQueueListing')->name('boards.queue');
    // board queue update api
    Route::post('board/queues', 'Api\SemanticBoardController@updateBoardQueueStatus')->name('boards.queue.update');

    /** response time logs */
    Route::get('response/time/logs', 'Api\ResponseTimeController@getResponseTime')->name('response.time');
    Route::delete('response/time/logs/delete/{id}', 'Api\ResponseTimeController@logDelete')->name('response.logDelete');

    /*
     * package routes
    */
    Route::post('packages', 'Api\Package\PackageController@store')->name('packages.store');
    Route::get('packages/{packageId}', 'Api\Package\PackageController@show')->name('packages.show');
    Route::get('packages', 'Api\Package\PackageController@index')->name('packages.index');
    Route::get('associate-companies/{packageId}', 'Api\Package\PackageController@getAssociateCompanies');
    Route::get('package/change/status/{packageId}/{status}', 'Api\Package\PackageController@changePackageStatus');

    /*
    * Campaign stats Routes
    *
    */
    Route::get('campaign/tracking/stats/{id}', 'Api\CampaignStatsController@trackingStats')->name('campaign.campaignTrackingStats');
    Route::get('campaign/tracking/list/{id}', 'Api\CampaignStatsController@campaignTracking')->name('campaign.campaignTracking');
    Route::get('campaign/tracking/export/{id}', 'Api\CampaignStatsController@campaignTrackingExport')->name('campaign.campaignTracking');
    Route::get('campaign/action/list/{id}', 'Api\CampaignStatsController@actionTrigger')->name('campaign.actionTrigger');
    Route::get('campaign/stats/{id}', 'Api\CampaignStatsController@campaignStats');
    Route::get('campaign/stats/views-clicks/{id}', 'Api\CampaignStatsController@campaignViewsClicksCount');
    Route::get('campaign/stats/chart/{id}', 'Api\CampaignStatsController@campaignViewsClicksChart');
    Route::get('campaign/stats/countries-chart/{id}', 'Api\CampaignStatsController@campaignCountriesChart');
    Route::get('campaign/stats/activity-chart/{id}', 'Api\CampaignStatsController@campaignActivityChart');
    Route::get('campaign/stats/link-activity-stats/{id}', 'Api\CampaignStatsController@campaignLinkActivityStats');
    Route::get('campaign/stats/campaign-activity-stats-export/{id}', 'Api\CampaignStatsController@campaignActivityStatsExport');
    Route::get('campaign/variants/{campaignId}', 'Api\CampaignStatsController@getCampaignVariants');
    Route::get('campaign/target-users-stats/{campaignId}', 'Api\CampaignStatsController@getTargetUsersStats');

    Route::post('campaign/queues', 'Api\CampaignsController@updateCampaignQueueStatus')->name('campaigns.queue.update');

    Route::post('campaign/resend/notification', 'Api\CampaignsController@resendNotification')->name('campaigns.resendNotification');

    Route::post('get/user/action/list', 'Api\CampaignsController@userActionList')->name('campaigns.userActionList');

    Route::get('newsFeed/stats/{id}', 'Api\NewsFeedStatsController@newsFeedStats');
    Route::get('newsFeed/stats/views-clicks/{id}', 'Api\NewsFeedStatsController@newsfeedViewsClicksCount');
    Route::get('newsfeed/stats/chart/{id}', 'Api\NewsFeedStatsController@newsfeedViewsClicksChart');


    /*
    * Campaign Api Triggers
    * */
    Route::post('campaign/action/trigger/send', 'Api\CampaignsController@actionTrigger');
    Route::post('campaign/api/trigger/send', 'Api\CampaignsController@apiTrigger');
    Route::post('campaign/conversion/trigger/send', 'Api\CampaignsController@conversionTrigger');
    Route::post('campaign/push/tracking/service', 'Api\CampaignsController@trackingService');

    /*
     * board push tracking service
     * */
    Route::post('board/push/tracking/service', 'Api\SemanticBoardController@trackingService');

    /*
     *
     * News Feed Api
     */
    Route::post('get/news_feed/listing', 'Api\NewsFeedController@getNewsFeedList');
    Route::post('get/action/list', 'Api\NewsFeedController@actionList')->name('newsfeeds.actionList');
    Route::post('get/news_feed/count', 'Api\NewsFeedController@newsFeedCount')->name('newsfeeds.newsFeedCount');
    /*
   * Segments Routes
   * */
//    Route::resource('segments', 'Api\SegmentsController');
    Route::get('segments', 'Api\SegmentsController@index')->name('segments.index');
    Route::post('segments', 'Api\SegmentsController@store')->name('segments.store');
    Route::get('segments/{segmentId}', 'Api\SegmentsController@show')->name('segments.show');
    Route::put('segments/{segment}', 'Api\SegmentsController@update')->name('segments.update');
    Route::delete('segments/{segment}', 'Api\SegmentsController@destroy')->name('segments.destroy');
    Route::get('segment/filters', 'Api\SegmentsController@getFilters')->name('segments.filters');
    Route::get('segment/export/{segmentId}', 'Api\SegmentsController@getExportUsers')->name('segments.export');
    Route::get('segment/change-status/{segmentId}/{status}', 'Api\SegmentsController@changeStatus')->name('segments.status');


    //    NewsFeed
    Route::get('newsfeeds', 'Api\NewsFeedController@index')->name('newsfeeds.index');
    Route::post('newsfeeds', 'Api\NewsFeedController@store')->name('newsfeeds.store');
    Route::get('newsfeeds/{newsfeedId}', 'Api\NewsFeedController@show')->name('newsfeeds.show');
    Route::put('newsfeeds/{newsfeed}', 'Api\NewsFeedController@update')->name('newsfeeds.update');
    Route::get('newsfeed/filters', 'Api\NewsFeedController@getFilters')->name('newsfeeds.filters');
    //Route::delete('segments/{segment}', 'Api\SegmentsController@destroy')->name('segments.destroy');
    //Route::get('segment/export/{segmentId}', 'Api\SegmentsController@getExportUsers')->name('segments.export');

    Route::get('dashboard/stats', 'Api\Stats\SummaryController@index');
    Route::get('dashboard/email/campaign', 'Api\Stats\SummaryController@getEmailCampaign');
    Route::get('dashboard/conversation/campaign', 'Api\Stats\SummaryController@getConversationCampaign');
    Route::get('dashboard/campaign-user/{type}/{deviceType?}', 'Api\Stats\SummaryController@getCampaignUserLatLng');
    Route::post('dashboard/campaign-stats/count', 'Api\Stats\SummaryController@getCampaignStatsCount');
    Route::post('dashboard/conversion-stats/count', 'Api\Stats\SummaryController@getConversionCount');
    Route::post('dashboard/newsfeed-stats/count', 'Api\Stats\SummaryController@getNewsFeedCount');
    Route::get('dashboard/recent-apps', 'Api\Stats\SummaryController@getRecentApps');
    Route::post('dashboard/user-stats/count', 'Api\Stats\SummaryController@getUserStatsCount');
    Route::post('stats/summary', 'Api\Stats\SummaryController@store');
    Route::post('user/subscribe', 'Api\User\SubscribeController@store');
    Route::post('update/bulk/users/import', 'Api\User\SubscribeController@bulkUserImport');
    Route::post('notifications/send', 'Api\Notifications\SendController@store');

    Route::post('notification/toggle', 'Api\AppUserController@toggleNotification');

    Route::resource('imports', 'Api\ImportDataController');
    Route::post('import/import-file', 'Api\ImportDataController@importTargetedUsers');
    Route::post('import/delete-file', 'Api\ImportDataController@deleteImportFile');

    Route::post('sendNotification', 'Api\ApiNotificationController@sendFcmNotification');

    Route::get('severTime', 'IndexController@getServerTime');
});
