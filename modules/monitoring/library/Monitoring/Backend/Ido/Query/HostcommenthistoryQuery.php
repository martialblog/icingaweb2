<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host comment history records
 */
class HostcommenthistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'commenthistory' => array(
            'host'          => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'     => 'ho.name1',
            'object_type'   => '(\'host\')'
        ),
        'history' => array(
            'type'      => "(CASE hch.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'dt_comment' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END)",
            'timestamp' => 'UNIX_TIMESTAMP(hch.comment_time)',
            'object_id' => 'hch.object_id',
            'state'     => '(NULL)',
            'output'    => "('[' || hch.author_name || '] ' || hch.comment_data)",
        ),
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'hosts' => array(
            'host_alias'        => 'h.alias',
            'host_display_name' => 'h.display_name COLLATE latin1_general_ci'
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci'
        ),
        'services' => array(
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_display_name'  => 's.display_name COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        )
    );

    /**
     * {@inheritdoc}
     */
    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'UNIX_TIMESTAMP(hch.comment_time)') {
            return 'hch.comment_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
        } else {
            return parent::whereToSql($col, $sign, $expression);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('hch' => $this->prefix . 'commenthistory'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = hch.object_id',
            array()
        )->where(
            'ho.is_active = ?',
            1
        )->where(
            'ho.objecttype_id = ?',
            1
        );
        $this->joinedVirtualTables['commenthistory'] = true;
        $this->joinedVirtualTables['history'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = ho.object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id',
            array()
        )->where(
            'hgo.is_active = ?',
            1
        )
        ->where(
            'hgo.objecttype_id = ?',
            3
        );
        $this->select->group(array('hch.commenthistory_id', 'ho.name1'));
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sg.' . $this->servicegroup_id . ' = sgm.servicegroup_id',
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id',
            array()
        )->where(
            'sgo.is_active = ?',
            1
        )
        ->where(
            'sgo.objecttype_id = ?',
            4
        );
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = ho.object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id',
            array()
        )->where(
            'so.is_active = ?',
            1
        )
        ->where(
            'so.objecttype_id = ?',
            2
        );
        $this->select->group(array('hch.commenthistory_id', 'ho.name1'));
    }
}
