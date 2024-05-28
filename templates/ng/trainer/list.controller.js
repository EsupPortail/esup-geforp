/**
 * Trainer List Controller
 */
sygeforApp.controller('TrainerListController', ['$scope', '$user', '$injector', 'search', 'BaseListController', '$state', '$timeout', '$dialog', function($scope, $user, $injector, search, BaseListController, $state, $timeout, $dialog) {
    $injector.invoke(BaseListController, this, {key: 'trainer', $scope: $scope, $search: search});

    // by default, order by createdat
    $scope.search.query.sorts = {'lastName.source': 'asc'};

    // facets
    $scope.facets = {
        'organization.name.source' : {
            label: 'Centre'
        },
        'institution.name.source' : {
            label: 'Unité'
        },
        'trainerType.source' : {
            label: 'Type d\'intervenant'
        },
        'isOrganization' : {
            label: 'Statut',
            values: {
                1: 'Formateur interne',
                0: 'Formateur extérieur'
            }
        },
        'isPublic' : {
            label: 'Publié',
            values: {
                '1': 'Oui',
                '0': 'Non'
            }
        },
        'isArchived' : {
            label: 'Archivé',
            values: {
                '1': 'Oui',
                '0': 'Non'
            }
        }
    };

    // batch operations
    $scope.batchOperations = [{
        icon: 'fa-envelope-o',
        label: 'Envoyer un Email',
        execute: function(items, $dialog) {
            return $dialog.open('batch.email', { items: items, targetClass: 'App\\Entity\\Core\\AbstractTrainer' })
        }
    },{
        icon: 'fa-download',
        label: 'Exporter',
        subitems: [
            {
                icon: 'fa-file-excel-o',
                label: 'CSV',
                execute: function(items, $dialog) {
                    return $dialog.open('batch.export.csv', { items: items, service: 'trainer' })
                }
            }
        ]

    }
        ];

    // add operations
    $scope.addOperations = [{
        label: 'Ajouter un formateur',
        execute: function () {
            $dialog.open('trainer.create').then(function(data) {
                $state.go('trainer.detail.view', {id: data.trainer.id}, {reload: true});
            })
        },
        available: function () {
            return $user.hasAccessRight('sygefor_trainer.rights.trainer.all.create') || $user.hasAccessRight('sygefor_trainer.rights.trainer.own.create');
        }
    }];
}]);
