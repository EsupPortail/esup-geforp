/**
 * Include a inscription table block for a given session
 * Usage : <span registration-label="session"></span>
 */
sygeforApp.directive('registrationStatsLabel', ['$compile', function($compile) {
    return {
        restrict: 'EA',
        terminal: true,
        priority: 1000, //this setting is important, see explanation below
        scope: {
            session: '=registrationStatsLabel'
        },
        link: function link(scope, element, attrs) {
            element.attr('tooltip', '{{ tooltip }}');
            element.addClass('label');
            element.removeAttr("registration-stats-label");
            scope.classes = [];

            if(element[0].tagName == 'A' && !attrs.uiSref) {
                element.attr('ui-sref', 'inscription.table({session: session.id})');
            }

            scope.$watch('session', function(session) {
               update(session, element, scope);
            });
            $compile(element)(scope);
        },
        template: '<i class="fa fa-male"></i>&nbsp; {{ label }}'
    }

    /**
     * @param session
     * @param element
     * @param scope
     */
    function update(session, element, scope) {

        // remove old classes
        for(var i=0; i<scope.classes.length; i++) {
            element.removeClass(scope.classes[i]);
        }
        scope.classes = [];

        if(!moment().isAfter(session.datebegin)) {
            // future session
            if(session.registration) {
                // inscription gérée individuellement
                scope.label = session.numberofacceptedregistrations + ' / ' + session.maximumnumberofregistrations;
                scope.tooltip = session.numberofacceptedregistrations + ' acceptés sur ' + session.maximumnumberofregistrations + ' places';
                scope.classes.push(scope.$root.sessionInscriptionStatsClass(session.numberofacceptedregistrations, session.maximumnumberofregistrations));
            } else {
                // inscription gérée globalement
                scope.label = session.numberofparticipants + ' / ' + session.maximumnumberofregistrations;
                scope.tooltip = session.numberofparticipants + ' participants sur ' + session.maximumnumberofregistrations + ' places';
                scope.classes.push('label-default');
            }
        } else {
            // past session
            scope.label = session.numberofparticipants;
            scope.tooltip = session.numberofparticipants + ' participants';
            if(session.numberofregistrations) {
                scope.label += ' / ' + session.numberofregistrations;
                scope.tooltip += ' sur ' +  session.numberofregistrations + ' inscrits';
            }
            scope.classes.push('label-transparent');
        }

        // apply new classes
        for(var i=0; i<scope.classes.length; i++) {
            element.addClass(scope.classes[i]);
        }
    }


}]);
