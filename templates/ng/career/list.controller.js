/**
 * Core List Controller
 */
sygeforApp.controller('CareerListController', ['$scope', '$user', '$injector', 'search', 'BaseListController', '$state', '$timeout', '$dialog', function ($scope, $user, $injector, search, BaseListController, $state, $timeout, $dialog) {
    $injector.invoke(BaseListController, this, {key: 'career', $scope: $scope, $search: search});

    // batch operations
    $scope.batchOperations = [
        {
            icon: 'fa-download',
            label: 'Exporter',
            subitems: [
                {
                    icon: 'fa-file-excel-o',
                    label: 'CSV',
                    execute: function (items, $dialog) {
                        return $dialog.open('batch.export.csv', {items: items, service: 'career'})
                    }
                }
            ]

        }
    ];

    // add operations
    $scope.addOperations = [{
        label: 'Ajouter un parcours',
        execute: function () {
            $dialog.open('career.create').then(function (result) {
                $state.go('career.detail.view', {id: result.career.id}, {reload: true});
            });
        },
        available: function () {
            return $user.hasAccessRight('sygefor_training.rights.training.all.create') || $user.hasAccessRight('sygefor_training.rights.training.own.create');
        }
    }];

    // facets
    $scope.facets = {
        'career.organization.name.source': {
            label: 'Centre'
        },
    };
}]);
