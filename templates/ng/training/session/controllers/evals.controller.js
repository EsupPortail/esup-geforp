/**
 * Core List Controller
 */
sygeforApp.controller('EvalComputeController', ['$scope', '$dialog', '$filter', '$taxonomy', '$timeout', '$q', function($scope, $dialog, $filter, $taxonomy, $timeout, $q) {

    // fetch all status and count
    $q.all([
        $taxonomy.getIndexedTerms('sygefor_mycompany.vocabulary_evaluation_criterion')
    ]).then(function(crit )  {
        $scope.crit = crit[0];
    });

    /**
     * Get the total accepted inscriptions count
     */
    $scope.totalAcceptedInscriptions = function() {
        return $filter('filter')($scope.session.inscriptions, {inscriptionstatus: {status: 2}}).length;
    }

    /**
     * Get the evaluated inscriptions count
     */
    $scope.totalEvaluatedInscriptions = function() {
        var nb=0;
        for (var i=0; i < $scope.session.inscriptions.length; i++) {
            var insc = $scope.session.inscriptions[i];
            if($(insc.criteria).length) {
                nb++;
            }
        }
        return nb;

    }

    /**
     * Get the average for a criterion
     */
    $scope.EvalAverage = function(criterion) {
        var nb=0;
        var average=0;
        for (var i=0; i < $scope.session.inscriptions.length; i++) {
            var insc = $scope.session.inscriptions[i];
            for (var j=0; j<insc.criteria.length; j++) {
                var crit = insc.criteria[j];
                if (crit.criterion.name == criterion.name) {
                    if (crit.note != 0) {
                        average = average + crit.note;
                        nb++;
                    }
                }
            }
        }
        if (nb>0){
            average = average/nb;
        }
        else {
            average = 0;
        }
        return average;

    }

}]);
