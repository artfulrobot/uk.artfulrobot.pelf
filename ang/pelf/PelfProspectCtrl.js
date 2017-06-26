(function(angular, $, _) {

  angular.module('pelf').controller('PelfProspectCtrl', function PelfProspectCtrl($scope, prospect) {
    console.log("PelfProspectCtrl ", prospect);
    $scope.crmUrl = CRM.url;
    $scope.prospect = prospect.values;
  });

})(angular, CRM.$, CRM._);

