/**
 * Include a trainers list block for a given session
 *
 * Usage : <div trainers-block="session"></div>
 */
sygeforApp.directive('trainersBlock', [function() {
    return {
        restrict: 'EA',
        scope: {
            session: '=trainersBlock'
        },
        link: function(scope, element, attrs) {
            // custum empty message
            scope.emptyMsg = attrs.emptyMsg ?  attrs.emptyMsg : "Il n'y a aucun formateur associé à cette session.";
        },
        controller: function($scope, $dialog, $taxonomy, $user, growl) {

            /**
             * Associate a new trainer
             */
            $scope.addTrainer = function () {
                $dialog.open('trainer.add', {session: $scope.session}).then(function (data){
                    $scope.session.participations.push(data.participation);
                });
            };

            /**
             * Edit costs
             * @param participation
             */
            $scope.editParticipation = function(participation) {
                $dialog.open('participation.edit', {participation: participation}).then(function (data) {
                    participation = data.participation;
                });
            };

            /**
             * Remove an associated trainer
             */
            $scope.removeTrainer = function (participation) {
                $dialog.open('trainer.remove', {session: $scope.session, participation: participation}).then(function (){
                    var index = $scope.session.participations.indexOf(participation);
                    if (index > -1) {
                        $scope.session.participations.splice(index, 1);
                    }
                });
            };

            /**
             * Send convocations to all trainers
             */
            $scope.sendConvo = function () {
                var items = [];
                for (var i=0; i < $scope.session.participations.length; i++) {
                    items.push($scope.session.participations[i].id);
                }

                $dialog.open('batch.email', {items: items, targetClass: 'SygeforMyCompanyBundle:Participation'});
            };

        },
        templateUrl: 'training/session/directives/trainers.block.html'
    }
}]);
