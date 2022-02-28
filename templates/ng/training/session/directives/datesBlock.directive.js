/**
 * Include a dates table block for a given session
 * Usage : <div dates-block="session"></div>
 */
sygeforApp.directive('datesBlock', ['$dialog', '$filter', function($dialog, $filter) {
    return {
        restrict: 'EA',
        scope: {
            session: '=datesBlock'
        },
        link: function(scope, element, attrs) {
            // custum empty message
            scope.emptyMsg = attrs.emptyMsg ?  attrs.emptyMsg : "Il n'y a aucune date pour cette session.";
            scope.$dialog = $dialog;
        },
        controller: 'DatesViewController',
        templateUrl: 'mycompanybundle/training/session/directives/dates.block.html'
    }
}]);



