import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import { Backup } from '@/types/backup';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/site-backups/components/columns';
import CreateSiteBackup from '@/pages/site-backups/components/create-site-backup';
import { PaginatedData } from '@/types';

type Page = {
  server: Server;
  site: any;
  backups: PaginatedData<Backup>;
};

export default function SiteBackups() {
  const page = usePage<Page>();

  return (
    <ServerLayout>
      <Head title={`Site Backups - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Site Backups" description={`Here you can manage the backups of ${page.props.site?.domain}`} />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/sites#backup" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateSiteBackup server={page.props.server} site={page.props.site}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateSiteBackup>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.backups} />
      </Container>
    </ServerLayout>
  );
}
