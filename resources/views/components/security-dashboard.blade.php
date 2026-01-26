<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🔒 Security Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Monitor security vulnerabilities and outdated packages</p>
            </div>
            <div class="flex gap-2">
                <form action="{{ route('security.run-audit') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors shadow-sm disabled:opacity-50" onclick="this.disabled=true;this.form.submit();">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Run Security Audit
                    </button>
                </form>

                <form action="{{ route('security.run-outdated') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md transition-colors shadow-sm disabled:opacity-50" onclick="this.disabled=true;this.form.submit();">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Check Outdated
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('status'))
        <div class="mb-8 rounded-md bg-green-50 dark:bg-green-900/30 p-4 border border-green-200 dark:border-green-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('status') }}
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" onclick="this.closest('.mb-8').remove()" class="inline-flex rounded-md p-1.5 text-green-500 hover:bg-green-100 dark:hover:bg-green-800 focus:outline-none">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Composer Audit -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Composer Security</h3>
                @if($latestComposerAudit?->has_issues)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                        {{ $latestComposerAudit->vulnerabilities_count }} Issues
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        Secure
                    </span>
                @endif
            </div>

            @if($latestComposerAudit)
                <div class="space-y-1">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $latestComposerAudit->vulnerabilities_count }}
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Last checked: {{ $latestComposerAudit->executed_at->diffForHumans() }}
                    </p>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No audit data</p>
            @endif
        </div>

        <!-- NPM Audit -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">NPM Security</h3>
                @if($latestNpmAudit?->has_issues)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                        {{ $latestNpmAudit->vulnerabilities_count }} Issues
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        Secure
                    </span>
                @endif
            </div>

            @if($latestNpmAudit)
                <div class="space-y-1">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $latestNpmAudit->vulnerabilities_count }}
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Last checked: {{ $latestNpmAudit->executed_at->diffForHumans() }}
                    </p>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No audit data</p>
            @endif
        </div>

        <!-- Composer Outdated -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Composer Outdated</h3>
                @if($latestComposerOutdated?->has_issues)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                        {{ $latestComposerOutdated->outdated_count }} Updates
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        Up to date
                    </span>
                @endif
            </div>

            @if($latestComposerOutdated)
                <div class="space-y-1">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $latestComposerOutdated->outdated_count }}
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Last checked: {{ $latestComposerOutdated->executed_at->diffForHumans() }}
                    </p>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No data</p>
            @endif
        </div>

        <!-- NPM Outdated -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">NPM Outdated</h3>
                @if($latestNpmOutdated?->has_issues)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                        {{ $latestNpmOutdated->outdated_count }} Updates
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        Up to date
                    </span>
                @endif
            </div>

            @if($latestNpmOutdated)
                <div class="space-y-1">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $latestNpmOutdated->outdated_count }}
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Last checked: {{ $latestNpmOutdated->executed_at->diffForHumans() }}
                    </p>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No data</p>
            @endif
        </div>
    </div>

    <!-- Issue Details -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Issue Details</h3>
        </div>

        <div class="p-6 space-y-8">
            {{-- 1. Composer Security Vulnerabilities --}}
            @if($latestComposerAudit?->has_issues)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <span class="text-red-500">●</span> Composer Vulnerabilities
                    </h3>
                    <ul class="space-y-3">
                        @foreach(($latestComposerAudit->results ?? []) as $vuln)
                            <li class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/10 rounded-r p-3">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $vuln['package'] ?? 'Unknown' }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                    {{ $vuln['title'] ?? 'No description' }}
                                    @if(isset($vuln['severity']))
                                        • Severity: <strong class="uppercase text-xs">{{ $vuln['severity'] }}</strong>
                                    @endif
                                    @if(isset($vuln['cve']))
                                        • CVE: {{ $vuln['cve'] }}
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 2. NPM Security Vulnerabilities --}}
            @if($latestNpmAudit?->has_issues)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <span class="text-red-500">●</span> NPM Vulnerabilities
                    </h3>
                    <ul class="space-y-3">
                        @foreach(($latestNpmAudit->results ?? []) as $vuln)
                            <li class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/10 rounded-r p-3">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $vuln['package'] ?? 'Unknown' }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                    {{ $vuln['title'] ?? 'No description' }}
                                    @if(isset($vuln['severity']))
                                        • Severity: <strong class="uppercase text-xs">{{ $vuln['severity'] }}</strong>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 3. Composer Outdated Packages --}}
            @if($latestComposerOutdated?->has_issues)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <span class="text-yellow-500">●</span> Composer Outdated
                    </h3>
                    <ul class="space-y-3">
                        @foreach(($latestComposerOutdated->results ?? []) as $pkg)
                            <li class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/10 rounded-r p-3 flex justify-between items-center">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $pkg['package'] ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $pkg['description'] ?? '' }}
                                    </div>
                                </div>
                                <div class="text-sm text-right">
                                    <div class="text-gray-500 dark:text-gray-400">Current: <span class="font-mono">{{ $pkg['current'] ?? '?' }}</span></div>
                                    <div class="font-semibold text-green-600 dark:text-green-400">Latest: <span class="font-mono">{{ $pkg['latest'] ?? '?' }}</span></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 4. NPM Outdated Packages --}}
            @if($latestNpmOutdated?->has_issues)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <span class="text-yellow-500">●</span> NPM Outdated
                    </h3>
                    <ul class="space-y-3">
                        @foreach(($latestNpmOutdated->results ?? []) as $pkg)
                            <li class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/10 rounded-r p-3 flex justify-between items-center">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $pkg['package'] ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Type: {{ $pkg['type'] ?? 'dependency' }}
                                    </div>
                                </div>
                                <div class="text-sm text-right">
                                    <div class="text-gray-500 dark:text-gray-400">Current: <span class="font-mono">{{ $pkg['current'] ?? '?' }}</span></div>
                                    <div class="font-semibold text-green-600 dark:text-green-400">Latest: <span class="font-mono">{{ $pkg['latest'] ?? '?' }}</span></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(
                !($latestComposerAudit?->has_issues) &&
                !($latestNpmAudit?->has_issues) &&
                !($latestComposerOutdated?->has_issues) &&
                !($latestNpmOutdated?->has_issues)
            )
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>No issues detected! Everything looks secure and up to date.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Audits Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Audit History</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Last 10 audits with issues</p>
        </div>

        @if($recentAudits && $recentAudits->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Source
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Issues
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Executed At
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentAudits as $audit)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $audit->type === 'audit' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                        {{ ucfirst($audit->type) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ ucfirst($audit->source) }}
                                        </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    @if($audit->type === 'audit')
                                        <span class="font-semibold text-red-600 dark:text-red-400">{{ $audit->vulnerabilities_count }}</span> vulnerabilities
                                    @else
                                        <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ $audit->outdated_count }}</span> outdated
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $audit->executed_at->format('Y-m-d H:i') }}
                                <span class="text-xs block">{{ $audit->executed_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($audit->has_issues)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                            Issues Found
                                        </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Clean
                                        </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No audit history</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Run your first security audit to see results here.</p>
            </div>
        @endif
    </div>
</div>
<script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
    };
</script>
