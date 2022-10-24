'use strict';

/* Directives */

angular.module('ngDateRange', [])
    .directive('dateRange', ['$timeout', function($timeout){
        return {

            restrict: 'AE',
            scope: {
                dateRangeChange: '=',
                dateRangeOptions: '=',
                tahIndex: '@?',
                dateRangeTbutton: '=?'
            },
            template:'<span class="SB-addon" ng-if="dateRangeTbutton" ng-click="toggleSingleDate()"><i class="glyphicon glyphicon-calendar fa fa-calendar" ></i>&nbsp;<i class="glyphicon glyphicon-calendar fa fa-calendar" ng-hide="hideRange"></i></span>',
            link: function (scope, element, attrs) {
                scope.dateRangeTbutton = scope.dateRangeTbutton || false;
                scope.tahIndex = scope.tahIndex || 0;
                if(scope.dateRangeTbutton){
                    if(attrs.value.indexOf('-')>-1){
                        scope.dateRangeOptions.singleDatePicker=false;
                    }else{
                        scope.dateRangeOptions.singleDatePicker=true;
                    };
                }
                scope.hideRange = scope.dateRangeOptions.singleDatePicker;
                if(scope.hideRange){
                    scope.dateRangeOptions.startDate = attrs.value;
                    scope.dateRangeOptions.endDate = attrs.value;
                }
                element.daterangepicker(scope.dateRangeOptions, scope.dateRangeChange);
                $timeout(function(){
                    element.parent().append(element.children().detach());
                });
                if(scope.hideRange){
                    element.data('daterangepicker').container.find('.calendar.left').after(element.data('daterangepicker').container.find('.calendar.right').detach());
                }
                scope.$watch("dateRangeOptions", function(dateRangeOptions){
                    element.data('daterangepicker').setOptions(dateRangeOptions,scope.dateRangeChange);
                    scope.hideRange = dateRangeOptions.singleDatePicker;
                    if(!scope.hideRange){
                        if(element.data('daterangepicker').container.find('.calendar.right').hasClass('single')){
                            element.data('daterangepicker').container.find('.calendar.left').show();
                            element.data('daterangepicker').container.find('.calendar.right').removeClass('single');
                        }
                    }
                });
                scope.$watch("dateRangeChange", function(dateRangeChange){
                    element.data('daterangepicker').setOptions(scope.dateRangeOptions,dateRangeChange);
                    scope.hideRange = scope.dateRangeOptions.singleDatePicker;
                });
                scope.toggleSingleDate = function(){

                    if(element.val() && scope.dateRangeOptions.singleDatePicker && (element.val().indexOf('-')==-1)){
                        element.val(element.val() + " - " + element.val()) ;
                        scope.dateRangeOptions.startDate = element.val() ;
                        scope.dateRangeOptions.endDate = element.val() ;
                    }
                    if(element.val() && !scope.dateRangeOptions.singleDatePicker && (element.val().indexOf('-')!=-1)){
                        element.val(element.val().slice(0,element.val().indexOf('-')-1)) ;
                        scope.dateRangeOptions.startDate = element.val() ;
                        scope.dateRangeOptions.endDate = element.val() ;
                    }
                    scope.dateRangeOptions.singleDatePicker = ! scope.dateRangeOptions.singleDatePicker;
                    scope.dateRangeOptions = angular.copy(scope.dateRangeOptions);
                    $timeout(function(){
                        element.data('daterangepicker').notify();
                        $timeout(function(){
                            element.focus();
                        });
                    });

                }
            }
        };
    }]);